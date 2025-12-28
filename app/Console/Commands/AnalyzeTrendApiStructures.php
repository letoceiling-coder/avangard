<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AnalyzeTrendApiStructures extends Command
{
    protected $signature = 'trend:analyze-structures';

    protected $description = 'Анализ структуры ответов TrendAgent API';

    public function handle()
    {
        $responsesDir = storage_path('app/trend_api_responses');
        
        if (!is_dir($responsesDir)) {
            $this->error("Директория не найдена: {$responsesDir}");
            return 1;
        }

        $this->info("🔍 Анализируем структуры ответов API...\n");

        $files = glob($responsesDir . '/*.json');
        $files = array_filter($files, function($file) {
            return basename($file) !== 'summary.json';
        });

        $analysis = [];
        
        foreach ($files as $file) {
            $filename = basename($file, '.json');
            $this->info("Анализируем: {$filename}");
            
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            
            if (!$data) {
                $this->warn("  ⚠️  Не удалось декодировать JSON");
                continue;
            }

            $structure = $this->analyzeStructure($data['data'] ?? $data, $filename);
            $analysis[$filename] = $structure;
        }

        // Сохраняем анализ
        $analysisFile = storage_path('app/trend_api_structures_analysis.json');
        file_put_contents(
            $analysisFile,
            json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // Создаем Markdown документ с анализом
        $this->createMarkdownDocumentation($analysis);

        $this->info("\n✅ Анализ завершен!");
        $this->info("📄 JSON анализ сохранен: {$analysisFile}");
        $this->info("📄 Markdown документация: storage/app/TREND_API_STRUCTURES.md");

        return 0;
    }

    private function analyzeStructure($data, string $context = '', int $depth = 0): array
    {
        $maxDepth = 5; // Ограничиваем глубину анализа
        
        if ($depth > $maxDepth) {
            return ['type' => 'max_depth_reached'];
        }

        $result = [
            'type' => gettype($data),
            'context' => $context,
        ];

        if (is_array($data)) {
            if (empty($data)) {
                $result['type'] = 'array_empty';
                return $result;
            }

            // Проверяем, является ли это ассоциативным массивом или списком
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            
            if ($isAssoc) {
                // Ассоциативный массив - объект
                $result['type'] = 'object';
                $result['properties'] = [];
                $result['sample_keys'] = array_keys(array_slice($data, 0, 20)); // Первые 20 ключей
                
                // Анализируем первые 3 элемента для понимания структуры
                $sampleCount = min(3, count($data));
                $samples = array_slice($data, 0, $sampleCount, true);
                
                foreach ($samples as $key => $value) {
                    $result['properties'][$key] = $this->analyzeProperty($key, $value, $depth + 1);
                }

                // Добавляем информацию о других ключах, если они есть
                if (count($data) > $sampleCount) {
                    $otherKeys = array_slice(array_keys($data), $sampleCount);
                    foreach ($otherKeys as $key) {
                        if (!isset($result['properties'][$key])) {
                            $result['properties'][$key] = $this->analyzeProperty($key, $data[$key], $depth + 1, true);
                        }
                    }
                }
            } else {
                // Список - массив
                $result['type'] = 'array';
                $result['count'] = count($data);
                
                if (count($data) > 0) {
                    // Анализируем первый элемент для понимания структуры элементов
                    $firstItem = $data[0];
                    $result['item_structure'] = $this->analyzeStructure($firstItem, $context . '[0]', $depth + 1);
                    
                    // Проверяем, все ли элементы имеют одинаковую структуру
                    if (count($data) > 1) {
                        $secondItem = $data[1];
                        $result['items_consistent'] = $this->compareStructures(
                            $this->analyzeStructure($firstItem, '', $depth + 1),
                            $this->analyzeStructure($secondItem, '', $depth + 1)
                        );
                    }
                }
            }
        } elseif (is_object($data)) {
            $result['type'] = 'object';
            $result['class'] = get_class($data);
            // Преобразуем объект в массив для анализа
            $result['properties'] = $this->analyzeStructure((array)$data, $context, $depth + 1);
        } else {
            // Простой тип
            $result['value_sample'] = is_string($data) && strlen($data) > 100 
                ? substr($data, 0, 100) . '...' 
                : $data;
        }

        return $result;
    }

    private function analyzeProperty(string $key, $value, int $depth, bool $quick = false): array
    {
        $analysis = [
            'key' => $key,
            'type' => gettype($value),
        ];

        if (is_array($value)) {
            if (empty($value)) {
                $analysis['type'] = 'array_empty';
            } elseif (!$quick && $depth < 4) {
                $analysis['structure'] = $this->analyzeStructure($value, $key, $depth);
            } else {
                $analysis['type'] = 'array';
                $analysis['count'] = count($value);
                $analysis['is_assoc'] = array_keys($value) !== range(0, count($value) - 1);
            }
        } elseif (is_string($value)) {
            $analysis['length'] = strlen($value);
            $analysis['sample'] = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
        } elseif (is_numeric($value)) {
            $analysis['value'] = $value;
        } elseif (is_bool($value)) {
            $analysis['value'] = $value;
        } elseif (is_null($value)) {
            $analysis['value'] = null;
        }

        return $analysis;
    }

    private function compareStructures(array $struct1, array $struct2): bool
    {
        // Простое сравнение типов
        if ($struct1['type'] !== $struct2['type']) {
            return false;
        }

        // Если это объекты, сравниваем ключи
        if ($struct1['type'] === 'object' && isset($struct1['sample_keys']) && isset($struct2['sample_keys'])) {
            return $struct1['sample_keys'] === $struct2['sample_keys'];
        }

        return true;
    }

    private function createMarkdownDocumentation(array $analysis): void
    {
        $md = "# Анализ структур данных TrendAgent API\n\n";
        $md .= "Документ автоматически сгенерирован на основе реальных ответов API\n\n";
        $md .= "**Дата создания:** " . now()->format('Y-m-d H:i:s') . "\n\n";
        $md .= "---\n\n";

        foreach ($analysis as $endpoint => $structure) {
            $md .= "## {$endpoint}\n\n";
            $md .= $this->structureToMarkdown($structure, 0);
            $md .= "\n---\n\n";
        }

        file_put_contents(storage_path('app/TREND_API_STRUCTURES.md'), $md);
    }

    private function structureToMarkdown(array $structure, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);
        $md = '';

        if ($structure['type'] === 'object' && isset($structure['properties'])) {
            $md .= "{$indent}**Тип:** Объект\n\n";
            
            if (isset($structure['sample_keys'])) {
                $md .= "{$indent}**Ключи:** " . implode(', ', array_slice($structure['sample_keys'], 0, 10));
                if (count($structure['sample_keys']) > 10) {
                    $md .= " ... (всего: " . count($structure['sample_keys']) . ")";
                }
                $md .= "\n\n";
            }

            $md .= "{$indent}**Свойства:**\n\n";

            foreach ($structure['properties'] as $key => $property) {
                $md .= "{$indent}- **`{$key}`** (" . $this->getTypeLabel($property['type']) . ")";
                
                if (isset($property['structure'])) {
                    $md .= "\n";
                    $subMd = $this->structureToMarkdown($property['structure'], $depth + 1);
                    // Добавляем отступ к каждой строке подструктуры
                    $subMd = preg_replace('/^/m', $indent . '  ', $subMd);
                    $md .= $subMd;
                } elseif (isset($property['length'])) {
                    $md .= " - длина: {$property['length']}";
                    if (isset($property['sample'])) {
                        $md .= ", пример: `{$property['sample']}`";
                    }
                } elseif (isset($property['value'])) {
                    $val = is_bool($property['value']) 
                        ? ($property['value'] ? 'true' : 'false') 
                        : (is_null($property['value']) ? 'null' : $property['value']);
                    $md .= " - значение: `{$val}`";
                } elseif (isset($property['count'])) {
                    $md .= " - элементов: {$property['count']}";
                }
                
                $md .= "\n";
            }
        } elseif ($structure['type'] === 'array') {
            $md .= "{$indent}**Тип:** Массив (элементов: {$structure['count']})\n\n";
            
            if (isset($structure['item_structure'])) {
                $md .= "{$indent}**Структура элемента:**\n\n";
                $subMd = $this->structureToMarkdown($structure['item_structure'], $depth + 1);
                $subMd = preg_replace('/^/m', $indent . '  ', $subMd);
                $md .= $subMd;
            }
        } else {
            $md .= "{$indent}**Тип:** " . $this->getTypeLabel($structure['type']) . "\n";
            if (isset($structure['value_sample'])) {
                $md .= "{$indent}**Пример:** `{$structure['value_sample']}`\n";
            }
        }

        return $md;
    }

    private function getTypeLabel(string $type): string
    {
        $labels = [
            'object' => 'object',
            'array' => 'array',
            'array_empty' => 'array (пустой)',
            'string' => 'string',
            'integer' => 'integer',
            'double' => 'float',
            'boolean' => 'boolean',
            'NULL' => 'null',
        ];

        return $labels[$type] ?? $type;
    }
}

