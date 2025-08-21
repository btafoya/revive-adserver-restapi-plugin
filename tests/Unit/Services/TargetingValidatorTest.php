<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TargetingValidator;
use PHPUnit\Framework\TestCase;

class TargetingValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_validates_empty_input(): void
    {
        $result = TargetingValidator::validate([]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('normalized', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('aclPreview', $result);
        $this->assertArrayHasKey('compiled', $result);
        $this->assertEmpty($result['normalized']);
        $this->assertEmpty($result['warnings']);
        $this->assertEmpty($result['aclPreview']);
        $this->assertSame('', $result['compiled']);
    }

    /**
     * @test
     */
    public function it_validates_simple_rule(): void
    {
        $input = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertEmpty($result['warnings']);
        $this->assertCount(1, $result['normalized']);
        $this->assertCount(1, $result['aclPreview']);
        
        $normalized = $result['normalized'][0];
        $this->assertSame('and', $normalized['logical']);
        $this->assertSame('Geo:Country', $normalized['type']);
        $this->assertSame('==', $normalized['comparison']);
        $this->assertSame('US', $normalized['data']);
        $this->assertSame(1, $normalized['order']);
    }

    /**
     * @test
     */
    public function it_adds_warning_for_unsupported_type(): void
    {
        $input = [
            [
                'type' => 'Unsupported:Type',
                'comparison' => '==',
                'data' => 'test'
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('Unsupported rule type', $result['warnings'][0]);
        $this->assertStringContainsString('Unsupported:Type', $result['warnings'][0]);
    }

    /**
     * @test
     */
    public function it_normalizes_invalid_comparison_operators(): void
    {
        $input = [
            [
                'type' => 'Geo:Country',
                'comparison' => 'invalid',
                'data' => 'US'
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('Invalid comparison', $result['warnings'][0]);
        $this->assertSame('==', $result['normalized'][0]['comparison']);
    }

    /**
     * @test
     */
    public function it_normalizes_invalid_logical_operators(): void
    {
        $input = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ],
            [
                'logical' => 'invalid',
                'type' => 'Client:Browser',
                'comparison' => '==',
                'data' => 'Chrome'
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('Invalid logical', $result['warnings'][0]);
        $this->assertSame('and', $result['normalized'][1]['logical']);
    }

    /**
     * @test
     */
    public function it_validates_time_day_of_week_ranges(): void
    {
        $input = [
            [
                'type' => 'Time:DayOfWeek',
                'comparison' => '==',
                'data' => [0, 1, 7, 'invalid']
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertGreaterThan(0, count($result['warnings']));
        $warnings = implode(' ', $result['warnings']);
        $this->assertStringContainsString('out of range', $warnings);
    }

    /**
     * @test
     */
    public function it_validates_time_hour_range(): void
    {
        $input = [
            [
                'type' => 'Time:HourRange',
                'data' => [
                    'from' => 25,
                    'to' => -1
                ]
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertGreaterThan(0, count($result['warnings']));
        $warnings = implode(' ', $result['warnings']);
        $this->assertStringContainsString('out of range', $warnings);
    }

    /**
     * @test
     */
    public function it_warns_about_hour_range_from_greater_than_to(): void
    {
        $input = [
            [
                'type' => 'Time:HourRange',
                'data' => [
                    'from' => 20,
                    'to' => 6
                ]
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertGreaterThan(0, count($result['warnings']));
        $warnings = implode(' ', $result['warnings']);
        $this->assertStringContainsString('from>to', $warnings);
        $this->assertStringContainsString('overnight', $warnings);
    }

    /**
     * @test
     */
    public function it_handles_site_variable_kv_format(): void
    {
        $input = [
            [
                'type' => 'Site:Variable',
                'comparison' => '==',
                'data' => 'existing',
                'kv' => [
                    'key1' => 'value1',
                    'key2' => 'value2'
                ]
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertEmpty($result['warnings']);
        $normalized = $result['normalized'][0];
        $this->assertIsArray($normalized['data']);
        $this->assertContains('existing', $normalized['data']);
        $this->assertContains('key1|value1', $normalized['data']);
        $this->assertContains('key2|value2', $normalized['data']);
    }

    /**
     * @test
     */
    public function it_warns_about_empty_kv_pairs(): void
    {
        $input = [
            [
                'type' => 'Site:Variable',
                'comparison' => '==',
                'kv' => [
                    'key1' => 'value1',
                    '' => 'empty_key',
                    'empty_value' => ''
                ]
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertGreaterThan(0, count($result['warnings']));
        $warnings = implode(' ', $result['warnings']);
        $this->assertStringContainsString('empty key/value', $warnings);
    }

    /**
     * @test
     */
    public function it_validates_groups(): void
    {
        $input = [
            [
                'group' => 'all',
                'rules' => [
                    [
                        'type' => 'Geo:Country',
                        'comparison' => '==',
                        'data' => 'US'
                    ]
                ]
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertEmpty($result['warnings']);
        $this->assertCount(1, $result['normalized']);
        
        $group = $result['normalized'][0];
        $this->assertSame('all', $group['group']);
        $this->assertSame('and', $group['logical']);
        $this->assertCount(1, $group['rules']);
    }

    /**
     * @test
     */
    public function it_normalizes_invalid_group_modes(): void
    {
        $input = [
            [
                'group' => 'invalid',
                'rules' => [
                    [
                        'type' => 'Geo:Country',
                        'comparison' => '==',
                        'data' => 'US'
                    ]
                ]
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('Unknown group mode', $result['warnings'][0]);
        $this->assertSame('all', $result['normalized'][0]['group']);
    }

    /**
     * @test
     */
    public function it_warns_about_empty_groups(): void
    {
        $input = [
            [
                'group' => 'all',
                'rules' => []
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('Empty group', $result['warnings'][0]);
    }

    /**
     * @test
     */
    public function it_skips_rules_with_missing_type(): void
    {
        $input = [
            [
                'comparison' => '==',
                'data' => 'test'
            ],
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('missing type', $result['warnings'][0]);
        $this->assertCount(1, $result['normalized']);
        $this->assertSame('Geo:Country', $result['normalized'][0]['type']);
    }

    /**
     * @test
     */
    public function it_generates_acl_preview(): void
    {
        $input = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => ['US', 'CA']
            ],
            [
                'logical' => 'or',
                'type' => 'Client:Browser',
                'comparison' => '==',
                'data' => 'Chrome'
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertCount(2, $result['aclPreview']);
        
        $first = $result['aclPreview'][0];
        $this->assertSame('and', $first['logical']);
        $this->assertSame('Geo:Country', $first['type']);
        $this->assertSame('==', $first['comparison']);
        $this->assertSame('["US","CA"]', $first['data']);
        $this->assertSame(1, $first['order']);
        
        $second = $result['aclPreview'][1];
        $this->assertSame('or', $second['logical']);
        $this->assertSame('Client:Browser', $second['type']);
        $this->assertSame('==', $second['comparison']);
        $this->assertSame('Chrome', $second['data']);
        $this->assertSame(2, $second['order']);
    }

    /**
     * @test
     */
    public function it_compiles_rules_through_targeting_compiler(): void
    {
        $input = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertNotEmpty($result['compiled']);
        $this->assertStringContainsString('MAX_checkGeo_Country', $result['compiled']);
        $this->assertStringContainsString('US', $result['compiled']);
    }

    /**
     * @test
     */
    public function it_handles_mixed_valid_and_invalid_rules(): void
    {
        $input = [
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
                'type' => 'Unsupported:Type',
                'comparison' => 'invalid_op',
                'data' => 'test'
            ],
            [
                'logical' => 'invalid_logical',
                'type' => 'Client:Browser',
                'comparison' => '==',
                'data' => 'Chrome'
            ]
        ];

        $result = TargetingValidator::validate($input);
        
        $this->assertCount(4, $result['warnings']);
        $this->assertCount(3, $result['normalized']); // Only 3 valid rules after normalization
        
        $warnings = implode(' ', $result['warnings']);
        $this->assertStringContainsString('missing type', $warnings);
        $this->assertStringContainsString('Unsupported rule type', $warnings);
        $this->assertStringContainsString('Invalid comparison', $warnings);
        $this->assertStringContainsString('Invalid logical', $warnings);
    }
}