<?php
/**
 * SSK Store - Unified Form & Page UX Helper Functions
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir)
 */

if (!function_exists('render_form_field')) {
    /**
     * Renders a standardized, accessible form field matching the UX system.
     *
     * @param string $name Field name
     * @param string $label Visible label
     * @param string $type Input type (text, email, tel, phone, pin, password, number, textarea, select, checkbox)
     * @param mixed $value Pre-filled value
     * @param array $options Configuration options (required, placeholder, hint, error, autocomplete, inputmode, options for select, rows for textarea)
     * @return string HTML output
     */
    function render_form_field(string $name, string $label, string $type = 'text', $value = '', array $options = []): string {
        $id = $options['id'] ?? ('field_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
        $required = !empty($options['required']);
        $optional = !empty($options['optional']);
        $placeholder = $options['placeholder'] ?? '';
        $hint = $options['hint'] ?? '';
        $error = $options['error'] ?? '';
        $autocomplete = $options['autocomplete'] ?? '';
        $inputmode = $options['inputmode'] ?? '';
        $maxlength = $options['maxlength'] ?? '';
        $disabled = !empty($options['disabled']);
        $readonly = !empty($options['readonly']);
        $extraClass = $options['class'] ?? '';
        $wrapperClass = $options['wrapper_class'] ?? 'form-group';
        $valEscaped = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

        $isInvalid = !empty($error);
        $ariaAttrs = '';
        if ($isInvalid) {
            $ariaAttrs .= ' aria-invalid="true" aria-describedby="' . $id . '_error"';
        } elseif (!empty($hint)) {
            $ariaAttrs .= ' aria-describedby="' . $id . '_hint"';
        }

        $html = '<div class="' . htmlspecialchars($wrapperClass) . '">';

        // Label
        if ($type !== 'checkbox' && $type !== 'hidden') {
            $html .= '<label class="form-label" for="' . htmlspecialchars($id) . '">';
            $html .= htmlspecialchars($label);
            if ($required) {
                $html .= ' <span class="form-label-required" aria-hidden="true">*</span>';
            } elseif ($optional) {
                $html .= ' <span class="form-label-optional">(optional)</span>';
            }
            $html .= '</label>';
        }

        // Special Inputs
        if ($type === 'phone' || ($type === 'tel' && !empty($options['is_india_phone']))) {
            // Phone with fixed +91 chip
            // Strip +91 if passed in value for display in the box
            $cleanVal = preg_replace('/^\+91\s*/', '', (string)$value);
            $cleanValEsc = htmlspecialchars($cleanVal, ENT_QUOTES, 'UTF-8');

            $html .= '<div class="input-phone-wrap ' . ($isInvalid ? 'is-invalid' : '') . '">';
            $html .= '<span class="phone-prefix-chip">🇮🇳 +91</span>';
            $html .= '<input type="tel" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" class="phone-input ' . htmlspecialchars($extraClass) . '" value="' . $cleanValEsc . '" placeholder="' . htmlspecialchars($placeholder ?: '98765 43210') . '" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" autocomplete="tel-national" enterkeyhint="next" ' . ($required ? 'required' : '') . ($disabled ? ' disabled' : '') . ($readonly ? ' readonly' : '') . $ariaAttrs . '>';
            $html .= '</div>';
        } elseif ($type === 'pin') {
            // 6-digit numeric PIN
            $html .= '<input type="text" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" class="form-input input-pin ' . ($isInvalid ? 'is-invalid ' : '') . htmlspecialchars($extraClass) . '" value="' . $valEscaped . '" placeholder="' . htmlspecialchars($placeholder ?: '395006') . '" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="postal-code" enterkeyhint="done" ' . ($required ? 'required' : '') . ($disabled ? ' disabled' : '') . ($readonly ? ' readonly' : '') . $ariaAttrs . '>';
        } elseif ($type === 'password') {
            // Password with show/hide toggle
            $html .= '<div class="input-password-wrap">';
            $html .= '<input type="password" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" class="form-input ' . ($isInvalid ? 'is-invalid ' : '') . htmlspecialchars($extraClass) . '" value="' . $valEscaped . '" placeholder="' . htmlspecialchars($placeholder) . '" autocomplete="' . htmlspecialchars($autocomplete ?: 'current-password') . '" ' . ($required ? 'required' : '') . ($disabled ? ' disabled' : '') . $ariaAttrs . '>';
            $html .= '<button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(\'' . htmlspecialchars($id) . '\', this)" aria-label="Toggle password visibility" tabindex="-1">👁️</button>';
            $html .= '</div>';
        } elseif ($type === 'select') {
            // Custom select
            $html .= '<select id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" class="form-select ' . ($isInvalid ? 'is-invalid ' : '') . htmlspecialchars($extraClass) . '" ' . ($required ? 'required' : '') . ($disabled ? ' disabled' : '') . $ariaAttrs . '>';
            if (!empty($options['placeholder'])) {
                $html .= '<option value="">' . htmlspecialchars($options['placeholder']) . '</option>';
            }
            $selectOptions = $options['options'] ?? [];
            foreach ($selectOptions as $optVal => $optLabel) {
                $selected = ((string)$optVal === (string)$value) ? ' selected' : '';
                $html .= '<option value="' . htmlspecialchars((string)$optVal) . '"' . $selected . '>' . htmlspecialchars((string)$optLabel) . '</option>';
            }
            $html .= '</select>';
        } elseif ($type === 'textarea') {
            $rows = $options['rows'] ?? 3;
            $html .= '<textarea id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" class="form-textarea ' . ($isInvalid ? 'is-invalid ' : '') . htmlspecialchars($extraClass) . '" rows="' . (int)$rows . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . ($required ? 'required' : '') . ($disabled ? ' disabled' : '') . ($readonly ? ' readonly' : '') . $ariaAttrs . '>' . $valEscaped . '</textarea>';
        } elseif ($type === 'checkbox') {
            $checked = !empty($value) ? ' checked' : '';
            $html .= '<label class="form-check" for="' . htmlspecialchars($id) . '">';
            $html .= '<input type="checkbox" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($options['checkbox_value'] ?? '1') . '" class="form-check-input ' . htmlspecialchars($extraClass) . '"' . $checked . ($disabled ? ' disabled' : '') . $ariaAttrs . '>';
            $html .= '<span>' . htmlspecialchars($label);
            if ($required) $html .= ' <span class="form-label-required">*</span>';
            $html .= '</span></label>';
        } else {
            // Standard text / email / number
            $extraAttrs = '';
            if ($autocomplete) $extraAttrs .= ' autocomplete="' . htmlspecialchars($autocomplete) . '"';
            if ($inputmode) $extraAttrs .= ' inputmode="' . htmlspecialchars($inputmode) . '"';
            if ($maxlength) $extraAttrs .= ' maxlength="' . (int)$maxlength . '"';
            if (!empty($options['step'])) $extraAttrs .= ' step="' . htmlspecialchars($options['step']) . '"';
            if (!empty($options['min'])) $extraAttrs .= ' min="' . htmlspecialchars($options['min']) . '"';
            if (!empty($options['max'])) $extraAttrs .= ' max="' . htmlspecialchars($options['max']) . '"';

            $html .= '<input type="' . htmlspecialchars($type) . '" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" class="form-input ' . ($isInvalid ? 'is-invalid ' : '') . htmlspecialchars($extraClass) . '" value="' . $valEscaped . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . ($required ? 'required' : '') . ($disabled ? ' disabled' : '') . ($readonly ? ' readonly' : '') . $extraAttrs . $ariaAttrs . '>';
        }

        // Hint
        if (!empty($hint)) {
            $html .= '<div class="form-hint" id="' . htmlspecialchars($id) . '_hint">' . htmlspecialchars($hint) . '</div>';
        }

        // Error message
        if ($isInvalid) {
            $html .= '<div class="form-error" id="' . htmlspecialchars($id) . '_error" role="alert"><span class="form-error-icon">⚠️</span> ' . htmlspecialchars($error) . '</div>';
        }

        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('render_card_start')) {
    function render_card_start(string $title, string $subtitle = '', $stepNum = null, string $id = '', string $class = ''): string {
        $idAttr = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
        $html = '<div class="ui-card ' . htmlspecialchars($class) . '"' . $idAttr . '>';
        if ($title || $stepNum !== null) {
            $html .= '<div class="ui-card-header">';
            $html .= '<div>';
            $html .= '<h3 class="ui-card-title">';
            if ($stepNum !== null) {
                $html .= '<span class="ui-card-step-num">' . htmlspecialchars((string)$stepNum) . '</span>';
            }
            $html .= htmlspecialchars($title) . '</h3>';
            if ($subtitle) {
                $html .= '<div class="ui-card-subtitle">' . htmlspecialchars($subtitle) . '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }
}

if (!function_exists('render_card_end')) {
    function render_card_end(): string {
        return '</div>';
    }
}

if (!function_exists('render_breadcrumb')) {
    function render_breadcrumb(array $crumbs): string {
        if (empty($crumbs)) return '';
        $normalized = [];
        foreach ($crumbs as $key => $val) {
            if (is_array($val)) {
                $normalized[] = [
                    'label' => (string)($val['label'] ?? $val['name'] ?? $val['title'] ?? ''),
                    'url' => (string)($val['url'] ?? $val['link'] ?? '')
                ];
            } else {
                $normalized[] = [
                    'label' => is_string($key) ? $key : (string)$val,
                    'url' => is_string($key) ? (string)$val : ''
                ];
            }
        }
        $html = '<nav class="ui-breadcrumb" aria-label="Breadcrumb">';
        $lastIdx = count($normalized) - 1;
        foreach ($normalized as $i => $item) {
            $label = $item['label'];
            $url = $item['url'];
            if ($i === $lastIdx || empty($url)) {
                $html .= '<span class="ui-breadcrumb-current" aria-current="page">' . htmlspecialchars($label) . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a>';
                $html .= '<span class="ui-breadcrumb-sep">/</span>';
            }
        }
        $html .= '</nav>';
        return $html;
    }
}

if (!function_exists('render_error_summary')) {
    function render_error_summary(array $errors): string {
        if (empty($errors)) return '';
        $html = '<div class="form-error-summary" role="alert" tabindex="-1">';
        $html .= '<div class="form-error-summary-title"><span>⚠️</span> Please fix the following errors:</div>';
        $html .= '<ul>';
        foreach ($errors as $fieldId => $msg) {
            if (is_numeric($fieldId)) {
                $html .= '<li>' . htmlspecialchars($msg) . '</li>';
            } else {
                $html .= '<li><a href="#' . htmlspecialchars($fieldId) . '">' . htmlspecialchars($msg) . '</a></li>';
            }
        }
        $html .= '</ul>';
        $html .= '</div>';
        return $html;
    }
}
