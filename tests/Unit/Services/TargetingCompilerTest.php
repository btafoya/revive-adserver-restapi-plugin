<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TargetingCompiler;
use PHPUnit\Framework\TestCase;

class TargetingCompilerTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_empty_string_for_empty_input(): void
    {
        $result = TargetingCompiler::compile([]);
        
        $this->assertSame('', $result);
    }

    /**
     * @test
     */
    public function it_compiles_single_rule(): void
    {
        $rules = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame("MAX_checkGeo_Country('US', '==')", $result);
    }

    /**
     * @test
     */
    public function it_compiles_multiple_rules_with_and_logic(): void
    {
        $rules = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ],
            [
                'logical' => 'and',
                'type' => 'Client:Browser',
                'comparison' => '==',
                'data' => 'Chrome'
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "MAX_checkGeo_Country('US', '==') MAX_checkClient_Browser('Chrome', '==')",
            $result
        );
    }

    /**
     * @test
     */
    public function it_compiles_rules_with_or_logic(): void
    {
        $rules = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ],
            [
                'logical' => 'or',
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'CA'
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "MAX_checkGeo_Country('US', '==') OR MAX_checkGeo_Country('CA', '==')",
            $result
        );
    }

    /**
     * @test
     */
    public function it_compiles_rules_with_not_logic(): void
    {
        $rules = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ],
            [
                'logical' => 'not',
                'type' => 'Client:Browser',
                'comparison' => '==',
                'data' => 'IE'
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "MAX_checkGeo_Country('US', '==') AND NOT (MAX_checkClient_Browser('IE', '=='))",
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_array_data_with_or_logic(): void
    {
        $rules = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => ['US', 'CA', 'UK']
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "(MAX_checkGeo_Country('US', '==') OR MAX_checkGeo_Country('CA', '==') OR MAX_checkGeo_Country('UK', '=='))",
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_time_day_of_week_specially(): void
    {
        $rules = [
            [
                'type' => 'Time:DayOfWeek',
                'comparison' => '==',
                'data' => [1, 2, 3]
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "(MAX_checkTime_DayOfWeek('1', '==') OR MAX_checkTime_DayOfWeek('2', '==') OR MAX_checkTime_DayOfWeek('3', '=='))",
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_time_hour_range(): void
    {
        $rules = [
            [
                'type' => 'Time:HourRange',
                'data' => [
                    'from' => '9',
                    'to' => '17'
                ]
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "(MAX_checkTime_HourOfDay('9', '>=') AND MAX_checkTime_HourOfDay('17', '<='))",
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_groups_with_all_mode(): void
    {
        $rules = [
            [
                'group' => 'all',
                'rules' => [
                    [
                        'type' => 'Geo:Country',
                        'comparison' => '==',
                        'data' => 'US'
                    ],
                    [
                        'type' => 'Client:Browser',
                        'comparison' => '==',
                        'data' => 'Chrome'
                    ]
                ]
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "((MAX_checkGeo_Country('US', '==')) AND (MAX_checkClient_Browser('Chrome', '==')))",
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_groups_with_any_mode(): void
    {
        $rules = [
            [
                'group' => 'any',
                'rules' => [
                    [
                        'type' => 'Geo:Country',
                        'comparison' => '==',
                        'data' => 'US'
                    ],
                    [
                        'type' => 'Geo:Country',
                        'comparison' => '==',
                        'data' => 'CA'
                    ]
                ]
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "((MAX_checkGeo_Country('US', '==')) OR (MAX_checkGeo_Country('CA', '==')))",
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_groups_with_none_mode(): void
    {
        $rules = [
            [
                'group' => 'none',
                'rules' => [
                    [
                        'type' => 'Client:Browser',
                        'comparison' => '==',
                        'data' => 'IE'
                    ]
                ]
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "NOT ((MAX_checkClient_Browser('IE', '==')))",
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_unsupported_types(): void
    {
        $rules = [
            [
                'type' => 'Unsupported:Type',
                'comparison' => '==',
                'data' => 'test'
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame("/* unsupported:Unsupported:Type == 'test' */ 1", $result);
    }

    /**
     * @test
     */
    public function it_normalizes_comparison_operators(): void
    {
        $rules = [
            [
                'type' => 'Geo:Country',
                'comparison' => 'invalid',
                'data' => 'US'
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame("MAX_checkGeo_Country('US', '==')", $result);
    }

    /**
     * @test
     */
    public function it_escapes_single_quotes_in_data(): void
    {
        $rules = [
            [
                'type' => 'Site:Variable',
                'comparison' => '==',
                'data' => "O'Reilly"
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame("MAX_checkSite_Variable('O\'Reilly', '==')", $result);
    }

    /**
     * @test
     */
    public function it_skips_empty_expressions(): void
    {
        $rules = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ],
            [
                'type' => '',
                'comparison' => '==',
                'data' => 'invalid'
            ],
            [
                'type' => 'Client:Browser',
                'comparison' => '==',
                'data' => 'Chrome'
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertSame(
            "MAX_checkGeo_Country('US', '==') MAX_checkClient_Browser('Chrome', '==')",
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_complex_nested_groups(): void
    {
        $rules = [
            [
                'group' => 'all',
                'rules' => [
                    [
                        'type' => 'Geo:Country',
                        'comparison' => '==',
                        'data' => 'US'
                    ],
                    [
                        'group' => 'any',
                        'logical' => 'and',
                        'rules' => [
                            [
                                'type' => 'Client:Browser',
                                'comparison' => '==',
                                'data' => 'Chrome'
                            ],
                            [
                                'type' => 'Client:Browser',
                                'comparison' => '==',
                                'data' => 'Firefox'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = TargetingCompiler::compile($rules);
        
        $this->assertStringContainsString('MAX_checkGeo_Country', $result);
        $this->assertStringContainsString('MAX_checkClient_Browser', $result);
        $this->assertStringContainsString('Chrome', $result);
        $this->assertStringContainsString('Firefox', $result);
    }
}