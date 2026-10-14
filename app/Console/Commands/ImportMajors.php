<?php

namespace App\Console\Commands;

use App\Models\Campus;
use App\Models\City;
use App\Models\DegreeLevel;
use App\Models\Faculty;
use App\Models\FieldOfStudy;
use App\Models\Institution;
use App\Models\Major;
use App\Models\Region;
use App\Models\Scholarship;
use App\Models\TawjihiBranch;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports the real major/institution/scholarship catalog from the spreadsheet the
 * user provided (18 columns, one row per major-at-an-institution). Column layout
 * is fixed (A-R); see the header check below. Safe to re-run: everything is
 * find-or-create'd / updated by name, so importing the same file twice just
 * refreshes the data instead of duplicating it.
 */
class ImportMajors extends Command
{
    protected $signature = 'import:majors {path? : Path to the .xlsx file (defaults to the bundled spreadsheet in the project root)}';

    protected $description = 'Import majors, institutions, campuses, and scholarships from the provided Excel catalog';

    private const EXPECTED_HEADER = [
        'A' => 'التخصص', 'B' => 'المؤسسة', 'C' => 'الدرجة العلمية', 'D' => 'نوع المؤسسة',
        'E' => 'المنطقة', 'F' => 'المدينة / الفرع', 'G' => 'الكلية / الوحدة', 'H' => 'مجال الدراسة',
    ];

    public function handle(): int
    {
        $path = $this->argument('path') ?? base_path('جامعه خضوري.xlsx');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $this->info("Reading {$path} ...");
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $header = array_shift($rows);
        foreach (self::EXPECTED_HEADER as $col => $expected) {
            if (trim((string) ($header[$col] ?? '')) !== $expected) {
                $this->error("Unexpected header in column {$col}: expected \"{$expected}\", found \"{$header[$col]}\". Aborting so bad data isn't imported.");

                return self::FAILURE;
            }
        }

        $count = 0;
        $bar = $this->output->createProgressBar(count($rows));

        foreach ($rows as $row) {
            if (trim((string) ($row['A'] ?? '')) === '') {
                $bar->advance();

                continue;
            }

            $this->importRow($row);
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Imported/updated {$count} majors.");

        return self::SUCCESS;
    }

    private function importRow(array $row): void
    {
        $region = $this->findOrCreateByName(Region::class, $row['E'] ?? null);
        $institution = $this->findOrCreateInstitution($row['B'] ?? null, $row['D'] ?? null, $row['K'] ?? null);
        $campuses = $this->findOrCreateCampuses($institution, $region, $row['F'] ?? null);
        $faculty = $institution ? $this->findOrCreateFaculty($institution, $row['G'] ?? null) : null;
        $degreeLevel = $this->findOrCreateByName(DegreeLevel::class, $row['C'] ?? null);
        $fieldOfStudy = $this->findOrCreateByName(FieldOfStudy::class, $row['H'] ?? null);
        $branches = $this->findOrCreateTawjihiBranches($row['L'] ?? null);
        // Scholarships aren't linked to majors (they're awarded to students by
        // admin, not tied to a specific major) — still worth importing the
        // catalog of scholarship *types* this institution offers, though.
        $this->findOrCreateScholarships($row['O'] ?? null);

        if (! $institution) {
            return;
        }

        $major = Major::updateOrCreate(
            ['institution_id' => $institution->id, 'name' => trim($row['A'])],
            [
                'faculty_id' => $faculty?->id,
                'degree_level_id' => $degreeLevel?->id,
                'field_of_study_id' => $fieldOfStudy?->id,
                'credit_hour_price' => $this->parseNumber($row['M'] ?? null),
                'credit_hours' => $this->parseNumber($row['N'] ?? null),
                'study_duration_text' => $this->cleanText($row['P'] ?? null),
                'admission_average' => $this->parseNumber($row['Q'] ?? null),
                'min_gpa_to_apply' => $this->parseNumber($row['R'] ?? null),
                'admission_link' => $this->cleanText($row['J'] ?? null),
            ]
        );

        $major->campuses()->sync($campuses->pluck('id'));
        $major->tawjihiBranches()->sync($branches->pluck('id'));
    }

    private function findOrCreateByName(string $modelClass, ?string $name): ?object
    {
        $name = $this->cleanText($name);
        if ($name === null) {
            return null;
        }

        return $modelClass::firstOrCreate(['name' => $name]);
    }

    private function findOrCreateInstitution(?string $name, ?string $type, ?string $website): ?Institution
    {
        $name = $this->cleanText($name);
        if ($name === null) {
            return null;
        }

        return Institution::firstOrCreate(
            ['name' => $name],
            ['institution_type' => $this->cleanText($type), 'official_website' => $this->cleanText($website)]
        );
    }

    private function findOrCreateFaculty(Institution $institution, ?string $name): ?Faculty
    {
        $name = $this->cleanText($name);
        if ($name === null) {
            return null;
        }

        return Faculty::firstOrCreate(['institution_id' => $institution->id, 'name' => $name]);
    }

    /**
     * The city column sometimes lists more than one branch, e.g. "طولكرم / رام الله" —
     * one campus is created per city and all are linked to the major.
     */
    private function findOrCreateCampuses(?Institution $institution, ?Region $region, ?string $cityCell): \Illuminate\Support\Collection
    {
        if (! $institution) {
            return collect();
        }

        $cityNames = collect(explode('/', (string) $cityCell))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique();

        if ($cityNames->isEmpty()) {
            return collect();
        }

        $regionId = ($region ?? Region::firstOrCreate(['name' => 'غير محدد']))->id;

        return $cityNames->map(function (string $cityName) use ($institution, $regionId) {
            $city = City::firstOrCreate(['region_id' => $regionId, 'name' => $cityName]);

            return Campus::firstOrCreate(['institution_id' => $institution->id, 'city_id' => $city->id]);
        });
    }

    /**
     * "جميع فروع الثانوية العامة" (any branch accepted) is intentionally left as
     * an empty set — MajorMatchingService treats "no branches recorded" as open
     * to every tawjihi branch, which is exactly this case.
     */
    private function findOrCreateTawjihiBranches(?string $cell): \Illuminate\Support\Collection
    {
        $cell = $this->cleanText($cell);
        if ($cell === null || str_contains($cell, 'جميع')) {
            return collect();
        }

        // Drop trailing notes like "+ امتحان اللياقة" or "(...)" — best-effort parse.
        $listPart = preg_split('/[+(]/u', $cell)[0];

        // Split on Arabic/Latin commas, and on a "و" ("and") glued directly onto
        // "ال" ("the"), e.g. "العلمي والصناعي والزراعي" -> العلمي / الصناعي / الزراعي.
        return collect(preg_split('/[،,]|\sو(?=ال)/u', $listPart))
            ->map(fn ($name) => trim(str_replace('فقط', '', $name)))
            ->filter()
            ->unique()
            ->map(fn ($name) => TawjihiBranch::firstOrCreate(['name' => $name]));
    }

    /**
     * The scholarships cell is free text like "2026-2027: أبناء الشهيد 100%؛ الأوائل
     * 100%؛ ..." — split into one Scholarship row per ؛-separated clause, pulling out
     * a discount percentage where present.
     */
    private function findOrCreateScholarships(?string $cell): \Illuminate\Support\Collection
    {
        $cell = $this->cleanText($cell);
        if ($cell === null) {
            return collect();
        }

        // Strip a leading "YYYY-YYYY: " academic-year label if present.
        $cell = preg_replace('/^\d{4}-\d{4}:\s*/u', '', $cell);

        return collect(explode('؛', $cell))
            ->map(fn ($clause) => trim($clause))
            ->filter()
            ->map(function (string $clause) {
                $discount = null;
                if (preg_match('/(\d+)\s*%/u', $clause, $m)) {
                    $discount = (float) $m[1];
                }

                return Scholarship::firstOrCreate(
                    ['name' => \Illuminate\Support\Str::limit($clause, 250, '')],
                    ['description' => $clause, 'discount_percentage' => $discount]
                );
            });
    }

    private function parseNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Pull the first number out of strings like "حوالي 4 سنوات".
        if (preg_match('/\d+(\.\d+)?/', (string) $value, $m)) {
            return (float) $m[0];
        }

        return null;
    }

    private function cleanText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
