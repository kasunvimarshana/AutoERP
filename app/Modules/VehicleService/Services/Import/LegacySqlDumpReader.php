<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Import;

use Generator;
use RuntimeException;

final class LegacySqlDumpReader
{
    /**
     * The dump is parsed as text. No statement from it is ever executed.
     *
     * @param  list<string>  $allowedTables
     * @return array<string, list<array<string, string|null>>>
     */
    public function read(string $path, array $allowedTables): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The legacy SQL dump could not be read.');
        }

        $allowed = array_fill_keys($allowedTables, true);
        $rows = array_fill_keys($allowedTables, []);

        foreach ($this->statements($path) as $statement) {
            if (! preg_match('/(?:^|\R)INSERT\s+INTO\s+`([^`]+)`/i', $statement, $tableMatch, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $table = $tableMatch[1][0];
            if (! isset($allowed[$table])) {
                continue;
            }

            $insert = substr($statement, $tableMatch[0][1]);
            if (! preg_match('/^\s*INSERT\s+INTO\s+`[^`]+`\s*\((.*?)\)\s*VALUES\s*(.*);\s*$/is', $insert, $insertMatch)) {
                throw new RuntimeException("Unsupported INSERT format for allowlisted table {$table}.");
            }

            preg_match_all('/`([^`]+)`/', $insertMatch[1], $columnMatches);
            $columns = $columnMatches[1];
            if ($columns === []) {
                throw new RuntimeException("No columns were found for allowlisted table {$table}.");
            }

            foreach ($this->tuples($insertMatch[2]) as $values) {
                if (count($values) !== count($columns)) {
                    throw new RuntimeException("Column/value count mismatch in allowlisted table {$table}.");
                }

                /** @var array<string, string|null> $row */
                $row = array_combine($columns, $values);
                $rows[$table][] = $row;
            }
        }

        return $rows;
    }

    /** @return Generator<int, string> */
    private function statements(string $path): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The legacy SQL dump could not be opened.');
        }

        $statement = '';
        $quote = null;
        $escaped = false;

        try {
            while (($chunk = fgets($handle)) !== false) {
                $length = strlen($chunk);
                for ($index = 0; $index < $length; $index++) {
                    $character = $chunk[$index];
                    $statement .= $character;

                    if ($escaped) {
                        $escaped = false;

                        continue;
                    }
                    if ($quote !== null && $character === '\\') {
                        $escaped = true;

                        continue;
                    }
                    if ($quote !== null) {
                        if ($character === $quote) {
                            if ($index + 1 < $length && $chunk[$index + 1] === $quote) {
                                $statement .= $chunk[++$index];
                            } else {
                                $quote = null;
                            }
                        }

                        continue;
                    }
                    if ($character === "'" || $character === '"') {
                        $quote = $character;

                        continue;
                    }
                    if ($character === ';') {
                        yield $statement;
                        $statement = '';
                    }
                }
            }

            if (trim($statement) !== '') {
                yield $statement;
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return Generator<int, list<string|null>> */
    private function tuples(string $valuesSql): Generator
    {
        $length = strlen($valuesSql);
        $inside = false;
        $quote = null;
        $escaped = false;
        $field = '';
        $values = [];

        for ($index = 0; $index < $length; $index++) {
            $character = $valuesSql[$index];

            if (! $inside) {
                if ($character === '(') {
                    $inside = true;
                    $field = '';
                    $values = [];
                }

                continue;
            }

            if ($escaped) {
                $field .= $character;
                $escaped = false;

                continue;
            }
            if ($quote !== null && $character === '\\') {
                $field .= $character;
                $escaped = true;

                continue;
            }
            if ($quote !== null) {
                $field .= $character;
                if ($character === $quote) {
                    if ($index + 1 < $length && $valuesSql[$index + 1] === $quote) {
                        $field .= $valuesSql[++$index];
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }
            if ($character === "'") {
                $quote = $character;
                $field .= $character;

                continue;
            }
            if ($character === ',') {
                $values[] = $this->decodeValue($field);
                $field = '';

                continue;
            }
            if ($character === ')') {
                $values[] = $this->decodeValue($field);
                yield $values;
                $inside = false;
                $field = '';
                $values = [];

                continue;
            }

            $field .= $character;
        }

        if ($inside || $quote !== null) {
            throw new RuntimeException('The legacy SQL dump contains an unterminated VALUES tuple.');
        }
    }

    private function decodeValue(string $token): ?string
    {
        $token = trim($token);
        if (strcasecmp($token, 'NULL') === 0) {
            return null;
        }
        if (! str_starts_with($token, "'") || ! str_ends_with($token, "'")) {
            return $token;
        }

        $value = substr($token, 1, -1);
        $decoded = '';
        $length = strlen($value);
        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];
            if ($character === "'" && $index + 1 < $length && $value[$index + 1] === "'") {
                $decoded .= "'";
                $index++;

                continue;
            }
            if ($character !== '\\' || $index + 1 >= $length) {
                $decoded .= $character;

                continue;
            }

            $escaped = $value[++$index];
            $decoded .= match ($escaped) {
                '0' => "\0",
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'Z' => chr(26),
                default => $escaped,
            };
        }

        return $decoded;
    }
}
