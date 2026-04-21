<?php
/**
 * 입력값 검증 클래스
 */
class Validator
{
    private $data = [];
    private $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required($field, $label)
    {
        $value = isset($this->data[$field]) ? trim($this->data[$field]) : '';
        if ($value === '') {
            $this->errors[$field] = "{$label}은(는) 필수 항목입니다.";
        }
        return $this;
    }

    public function maxLength($field, $max, $label)
    {
        $value = isset($this->data[$field]) ? $this->data[$field] : '';
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = "{$label}은(는) {$max}자 이내로 입력해주세요.";
        }
        return $this;
    }

    public function email($field, $label)
    {
        $value = isset($this->data[$field]) ? $this->data[$field] : '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} 형식이 올바르지 않습니다.";
        }
        return $this;
    }

    public function inList($field, array $list, $label)
    {
        $value = isset($this->data[$field]) ? $this->data[$field] : '';
        if ($value !== '' && !in_array($value, $list, true)) {
            $this->errors[$field] = "{$label} 값이 올바르지 않습니다.";
        }
        return $this;
    }

    public function date($field, $label)
    {
        $value = isset($this->data[$field]) ? $this->data[$field] : '';
        if ($value !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->errors[$field] = "{$label} 날짜 형식이 올바르지 않습니다. (YYYY-MM-DD)";
            }
        }
        return $this;
    }

    public function passes()
    {
        return empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }

    public function firstError()
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
