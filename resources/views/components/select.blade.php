@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => 'Select',
    'multiple' => false,
    'col' => 'col-md-6',
])
@php
    $field = str_replace('[]', '', $name);
    $current = old($field, $selected);
    $isList = array_is_list(is_array($options) ? $options : $options->all());
    $selectedValues = collect(is_array($current) ? $current : [$current])->map(fn ($v) => (string) $v)->all();
@endphp
<div class="{{ $col }}">
    @if($label)
        <label for="{{ $field }}" class="form-label small fw-medium">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <select name="{{ $name }}" id="{{ $field }}" {{ $attributes->merge(['class' => 'form-select'.($errors->has($field) ? ' is-invalid' : '')]) }} @required($required) @if($multiple) multiple @endif>
        @unless($multiple)<option value="">{{ $placeholder }}</option>@endunless
        @foreach($options as $value => $text)
            @php($value = $isList ? $text : $value)
            <option value="{{ $value }}" @selected(in_array((string) $value, $selectedValues, true))>{{ $text }}</option>
        @endforeach
    </select>
    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
