<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  § 5  ServerSideValidator
// ═══════════════════════════════════════════════════════════════════
/**
 * Chainable server-side validator.
 *
 * String rules: required | email | url | numeric | integer
 *   min:N | max:N | min_value:N | max_value:N
 *   in:a,b,c | not_in:a,b,c | regex:/pattern/
 *
 * Closure rules receive ($value, $fullDataArray) → true | false | string.
 *
 * Usage:
 *   ServerSideValidator::check($_POST)
 *       ->field('email', 'required|email')
 *       ->field('name',  'required|min:2|max:120', 'Full name')
 *       ->field('age',   'required|integer|min_value:18', 'Age')
 *       ->renderOnFail()   // auto-renders MicroWebPage and exits on failure
 *       ->passes();        // true / false
 */
final class ServerSideValidator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var list<array{field:string, rule:string|\Closure, label:string}> */
    private array $rules = [];

    /** @var list<string> */
    private array $errors = [];

    private bool $evaluated = false;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function check(array $data): self { return new self($data); }

    /** @param string|\Closure(mixed, array<string,mixed>): (bool|string) $rule */
    public function field(string $field, string|\Closure $rule, string $label = ''): self
    {
        $this->rules[] = [
            'field' => $field,
            'rule'  => $rule,
            'label' => $label !== '' ? $label : ucfirst(str_replace('_', ' ', $field)),
        ];
        return $this;
    }

    public function evaluate(): self
    {
        if ($this->evaluated) return $this;
        $this->evaluated = true;

        foreach ($this->rules as $entry) {
            ['field' => $field, 'label' => $label, 'rule' => $rule] = $entry;
            $value = $this->data[$field] ?? null;

            if ($rule instanceof \Closure) {
                $result = $rule($value, $this->data);
                if ($result !== true) {
                    $this->errors[] = is_string($result) ? $result : "'{$label}' failed validation.";
                }
                continue;
            }

            foreach (explode('|', $rule) as $token) {
                $error = self::applyToken($token, $label, $value);
                if ($error !== null) { $this->errors[] = $error; break; }
            }
        }
        return $this;
    }

    public function passes(): bool       { return $this->evaluate()->errors === []; }
    public function fails(): bool        { return !$this->passes(); }
    /** @return list<string> */
    public function errors(): array      { return $this->evaluate()->errors; }
    public function firstError(): string { return $this->evaluate()->errors[0] ?? ''; }

    /** @param \Closure(string, list<string>): void $callback */
    public function onFail(\Closure $callback): self
    {
        if ($this->fails()) $callback($this->firstError(), $this->errors());
        return $this;
    }

    /** @param \Closure(MicroWebPage, string): void|null $customise */
    public function renderOnFail(?\Closure $customise = null): self
    {
        if ($this->fails()) {
            $builder = MicroWebPage::create()->validationFailed($this->firstError());
            if ($customise !== null) $customise($builder, $this->firstError());
            $builder->render();
        }
        return $this;
    }

    private static function applyToken(string $token, string $label, mixed $value): ?string
    {
        $str    = is_string($value) ? trim($value) : (string) ($value ?? '');
        [$name, $param] = str_contains($token, ':') ? explode(':', $token, 2) : [$token, ''];

        return match ($name) {
            'required'  => ($value === null || $value === '' || $value === []) ? "'{$label}' is required." : null,
            'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) === false  ? "'{$label}' must be a valid email address." : null,
            'url'       => filter_var($value, FILTER_VALIDATE_URL) === false    ? "'{$label}' must be a valid URL." : null,
            'numeric'   => !is_numeric($value)                                  ? "'{$label}' must be numeric." : null,
            'integer'   => filter_var($value, FILTER_VALIDATE_INT) === false    ? "'{$label}' must be an integer." : null,
            'min'       => mb_strlen($str) < (int) $param                       ? "'{$label}' must be at least {$param} characters." : null,
            'max'       => mb_strlen($str) > (int) $param                       ? "'{$label}' must not exceed {$param} characters." : null,
            'min_value' => (float) $value < (float) $param                      ? "'{$label}' must be at least {$param}." : null,
            'max_value' => (float) $value > (float) $param                      ? "'{$label}' must not exceed {$param}." : null,
            'in'        => !in_array($str, explode(',', $param), true)           ? "'{$label}' contains an unacceptable value." : null,
            'not_in'    => in_array($str, explode(',', $param), true)            ? "'{$label}' contains a disallowed value." : null,
            'regex'     => !preg_match($param, $str)                             ? "'{$label}' format is invalid." : null,
            default     => null,
        };
    }
}
