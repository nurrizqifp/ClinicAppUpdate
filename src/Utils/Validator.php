<?php
namespace App\Utils;

class Validator {
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function make(array $data, array $rules): self {
        $instance = new self($data);
        foreach ($rules as $field => $ruleStr) {
            $instance->applyRules($field, $ruleStr);
        }
        return $instance;
    }

    private function applyRules(string $field, string $ruleStr): void {
        $rules = explode('|', $ruleStr);
        $value = $this->data[$field] ?? null;

        foreach ($rules as $rule) {
            [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

            match($ruleName) {
                'required'  => $this->required($field, $value),
                'email'     => $this->email($field, $value),
                'min'       => $this->min($field, $value, (int)$param),
                'max'       => $this->max($field, $value, (int)$param),
                'numeric'   => $this->numeric($field, $value),
                'in'        => $this->in($field, $value, explode(',', $param ?? '')),
                'date'      => $this->date($field, $value),
                'not_empty' => $this->required($field, $value),
                default     => null,
            };
        }
    }

    private function required(string $field, mixed $value): void {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field][] = "Field '{$field}' is required.";
        }
    }

    private function email(string $field, mixed $value): void {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Field '{$field}' must be a valid email address.";
        }
    }

    private function min(string $field, mixed $value, int $min): void {
        if (is_numeric($value)) {
            if ((float)$value < $min) {
                $this->errors[$field][] = "Field '{$field}' must be at least {$min}.";
            }
        } elseif (is_string($value) && strlen($value) < $min) {
            $this->errors[$field][] = "Field '{$field}' must be at least {$min} characters.";
        }
    }

    private function max(string $field, mixed $value, int $max): void {
        if (is_numeric($value)) {
            if ((float)$value > $max) {
                $this->errors[$field][] = "Field '{$field}' must be at most {$max}.";
            }
        } elseif (is_string($value) && strlen($value) > $max) {
            $this->errors[$field][] = "Field '{$field}' must be at most {$max} characters.";
        }
    }

    private function numeric(string $field, mixed $value): void {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = "Field '{$field}' must be numeric.";
        }
    }

    private function in(string $field, mixed $value, array $allowed): void {
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "Field '{$field}' must be one of: " . implode(', ', $allowed) . ".";
        }
    }

    private function date(string $field, mixed $value): void {
        if ($value !== null && $value !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->errors[$field][] = "Field '{$field}' must be a valid date (YYYY-MM-DD).";
            }
        }
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function passes(): bool {
        return empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function firstError(): string {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? '';
        }
        return '';
    }
}
