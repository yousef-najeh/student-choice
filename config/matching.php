<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Major-matching weights
    |--------------------------------------------------------------------------
    |
    | Each dimension is scored 0-100 for a given student/major pair, then
    | combined using these weights (they should add up to 1.0) into a single
    | 0-100 match score. Tune these without touching MajorMatchingService.
    |
    */

    'weights' => [
        'preferences' => 0.30,
        'gpa' => 0.25,
        'tawjihi' => 0.20,
        'budget' => 0.15,
        'location' => 0.10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Partial-match scores
    |--------------------------------------------------------------------------
    |
    | Used when a dimension can't be fully evaluated (e.g. the student hasn't
    | set a GPA yet) or only partially matches (e.g. same region, different
    | city). Keeping these here makes the scoring behavior easy to tune.
    |
    */

    'neutral_score' => 50.0,
    'same_region_score' => 60.0,
    'gpa_between_min_and_average_floor' => 40.0,

];
