@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'col' => 'col-md-6',
    'help' => null,
])
<div class="{{ $col }}">
    @if($label)
        <label for="{{ $name }}" class="form-label small fw-medium">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @endif
    @if($type === 'textarea')
        <textarea name="{{ $name }}" id="{{ $name }}" {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : ''), 'rows' => 3]) }} @required($required)>{{ old($name, $value) }}</textarea>
    @else
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }} @required($required)>
    @endif
    @if($help)<div class="form-text">{{ $help }}</div>@endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
