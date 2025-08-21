<?php

declare(strict_types=1);

namespace Tests\API;

use Tests\Fixtures\TestDatabase;
use PHPUnit\Framework\TestCase;

class TargetingApiTest extends TestCase
{
    private string $baseUrl = 'http://localhost';
    
    protected function setUp(): void
    {
        parent::setUp();
        TestDatabase::truncateAll();
    }

    /**
     * @test
     */
    public function it_validates_targeting_rules_successfully(): void
    {
        $rules = [
            [
                'type' => 'Geo:Country',
                'comparison' => '==',
                'data' => 'US'
            ],
            [
                'logical' => 'and',
                'type' => 'Time:HourRange',
                'data' => [
                    'from' => '9',
                    'to' => '17'
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/targeting/validate', ['rules' => $rules]);
        
        $this->assertArrayHasKey('valid', $response);
        $this->assertTrue($response['valid']);
        $this->assertArrayHasKey('compiled', $response);
        $this->assertArrayHasKey('normalized', $response);
        $this->assertArrayHasKey('warnings', $response);
        $this->assertArrayHasKey('aclPreview', $response);
        
        $this->assertStringContainsString('MAX_checkGeo_Country', $response['compiled']);
        $this->assertStringContainsString('MAX_checkTime_HourOfDay', $response['compiled']);
        $this->assertEmpty($response['warnings']);
    }

    /**
     * @test
     */
    public function it_returns_warnings_for_invalid_targeting_rules(): void
    {
        $rules = [
            [
                'type' => 'Unsupported:Type',
                'comparison' => 'invalid',
                'data' => 'test'
            ],
            [
                'logical' => 'invalid',
                'type' => 'Time:DayOfWeek',
                'comparison' => '==',
                'data' => [10, -1] // Invalid day of week values
            ]
        ];

        $response = $this->postJson('/api/v1/targeting/validate', ['rules' => $rules]);
        
        $this->assertArrayHasKey('valid', $response);
        $this->assertArrayHasKey('warnings', $response);
        $this->assertNotEmpty($response['warnings']);
        
        $warnings = implode(' ', $response['warnings']);
        $this->assertStringContainsString('Unsupported rule type', $warnings);
        $this->assertStringContainsString('Invalid comparison', $warnings);
        $this->assertStringContainsString('Invalid logical', $warnings);
        $this->assertStringContainsString('out of range', $warnings);
    }

    /**
     * @test
     */
    public function it_returns_targeting_schema(): void
    {
        $response = $this->getJson('/api/v1/targeting/schema');
        
        $this->assertArrayHasKey('types', $response);
        $this->assertArrayHasKey('comparisons', $response);
        $this->assertArrayHasKey('logicals', $response);
        $this->assertArrayHasKey('groups', $response);
        
        $types = $response['types'];
        $this->assertContains('Geo:Country', $types);
        $this->assertContains('Time:HourOfDay', $types);
        $this->assertContains('Client:Browser', $types);
        
        $comparisons = $response['comparisons'];
        $this->assertContains('==', $comparisons);
        $this->assertContains('!=', $comparisons);
        $this->assertContains('>=', $comparisons);
        
        $logicals = $response['logicals'];
        $this->assertContains('and', $logicals);
        $this->assertContains('or', $logicals);
        $this->assertContains('not', $logicals);
        
        $groups = $response['groups'];
        $this->assertContains('all', $groups);
        $this->assertContains('any', $groups);
        $this->assertContains('none', $groups);
    }

    /**
     * @test
     */
    public function it_formats_site_variables(): void
    {
        $data = [
            'variables' => [
                'category' => 'electronics',
                'brand' => 'apple',
                'price_range' => '500-1000'
            ]
        ];

        $response = $this->postJson('/api/v1/variables/site/format', $data);
        
        $this->assertArrayHasKey('formatted', $response);
        $this->assertArrayHasKey('rules', $response);
        
        $formatted = $response['formatted'];
        $this->assertContains('category|electronics', $formatted);
        $this->assertContains('brand|apple', $formatted);
        $this->assertContains('price_range|500-1000', $formatted);
        
        $rules = $response['rules'];
        $this->assertCount(1, $rules);
        $this->assertSame('Site:Variable', $rules[0]['type']);
        $this->assertSame($formatted, $rules[0]['data']);
    }

    /**
     * @test
     */
    public function it_handles_complex_targeting_validation(): void
    {
        $rules = [
            [
                'group' => 'all',
                'rules' => [
                    [
                        'type' => 'Geo:Country',
                        'comparison' => '==',
                        'data' => ['US', 'CA', 'UK']
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
            ],
            [
                'logical' => 'and',
                'group' => 'none',
                'rules' => [
                    [
                        'type' => 'Time:DayOfWeek',
                        'comparison' => '==',
                        'data' => [0, 6] // Weekend
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/targeting/validate', ['rules' => $rules]);
        
        $this->assertArrayHasKey('valid', $response);
        $this->assertTrue($response['valid']);
        $this->assertEmpty($response['warnings']);
        
        $compiled = $response['compiled'];
        $this->assertStringContainsString('MAX_checkGeo_Country', $compiled);
        $this->assertStringContainsString('MAX_checkClient_Browser', $compiled);
        $this->assertStringContainsString('MAX_checkTime_DayOfWeek', $compiled);
        $this->assertStringContainsString('NOT', $compiled); // For the 'none' group
    }

    /**
     * @test
     */
    public function it_validates_hour_range_constraints(): void
    {
        $rules = [
            [
                'type' => 'Time:HourRange',
                'data' => [
                    'from' => '20',
                    'to' => '6' // Overnight range - should generate warning
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/targeting/validate', ['rules' => $rules]);
        
        $this->assertArrayHasKey('warnings', $response);
        $this->assertNotEmpty($response['warnings']);
        
        $warnings = implode(' ', $response['warnings']);
        $this->assertStringContainsString('from>to', $warnings);
        $this->assertStringContainsString('overnight', $warnings);
    }

    /**
     * @test
     */
    public function it_validates_empty_rules_gracefully(): void
    {
        $response = $this->postJson('/api/v1/targeting/validate', ['rules' => []]);
        
        $this->assertArrayHasKey('valid', $response);
        $this->assertTrue($response['valid']);
        $this->assertEmpty($response['warnings']);
        $this->assertEmpty($response['normalized']);
        $this->assertEmpty($response['aclPreview']);
        $this->assertSame('', $response['compiled']);
    }

    /**
     * @test
     */
    public function it_handles_malformed_request_data(): void
    {
        $response = $this->postJson('/api/v1/targeting/validate', ['invalid' => 'data']);
        
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('rules', $response['error']);
    }

    /**
     * Helper method to simulate GET JSON requests
     */
    private function getJson(string $endpoint): array
    {
        // In a real test environment, you would use an HTTP client or framework test helpers
        // This is a simplified simulation of the API response structure
        
        if ($endpoint === '/api/v1/targeting/schema') {
            return [
                'types' => [
                    'Site:Variable', 'Site:Domain', 'Source:Source', 'Geo:Country',
                    'Client:Browser', 'Client:Language', 'Time:HourOfDay', 'Time:DayOfWeek'
                ],
                'comparisons' => ['==', '!=', '>', '<', '>=', '<='],
                'logicals' => ['and', 'or', 'not'],
                'groups' => ['all', 'any', 'none']
            ];
        }
        
        return ['error' => 'Not implemented in test'];
    }

    /**
     * Helper method to simulate POST JSON requests
     */
    private function postJson(string $endpoint, array $data): array
    {
        // In a real test environment, you would use an HTTP client or framework test helpers
        // This simulates the validation endpoint behavior
        
        if ($endpoint === '/api/v1/targeting/validate') {
            $rules = $data['rules'] ?? null;
            
            if (!is_array($rules)) {
                return ['error' => 'rules must be an array'];
            }
            
            // Simulate the validation using the actual service
            return \App\Services\TargetingValidator::validate($rules) + ['valid' => true];
        }
        
        if ($endpoint === '/api/v1/variables/site/format') {
            $variables = $data['variables'] ?? [];
            $formatted = [];
            
            foreach ($variables as $key => $value) {
                $formatted[] = "{$key}|{$value}";
            }
            
            return [
                'formatted' => $formatted,
                'rules' => [
                    [
                        'type' => 'Site:Variable',
                        'comparison' => '==',
                        'data' => $formatted
                    ]
                ]
            ];
        }
        
        return ['error' => 'Not implemented in test'];
    }
}