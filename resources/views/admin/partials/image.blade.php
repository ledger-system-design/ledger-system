{{--
    图片上传字段组件
    用法：
      @include('admin.partials.image', [
          'name'  => 'indexpic',                 // 表单字段名
          'value' => $order->indexpic ?? '',     // 当前值
          'label' => '商品图片',                  // 显示名称（可空）
          'size'  => 84,                          // 预览尺寸 px（可空，默认 84）
      ])
--}}
@php
    $size = $size ?? 84;
    $id = 'img_' . str_replace(['[', ']'], '_', $name) . '_' . substr(md5($name . ($value ?? '')), 0, 6);
@endphp

<div class="img-field" id="{{ $id }}">
    @if (!empty($label))
        <label class="img-field__label">{{ $label }}</label>
    @endif
    <div class="img-field__row">
        <div class="img-field__thumb" onclick="pickImage(this)" title="点击选择图片">
            <img class="img-field__preview" src="{{ $value ?: '' }}" alt=""
                style="{{ $value ? '' : 'display:none' }};width:{{ $size }}px;height:{{ $size }}px;">
            <div class="img-field__plus" style="{{ $value ? 'display:none' : '' }}">+<span>上传</span></div>
        </div>
        <div class="img-field__meta">
            <div class="btn-row">
                <button type="button" class="btn btn--sm btn--primary" onclick="pickImage(this)">选择图片上传</button>
                <button type="button" class="btn btn--sm"
                    onclick="var w=this.closest('.img-field');w.querySelector('.img-field__input').value='';w.querySelector('.img-field__preview').style.display='none';w.querySelector('.img-field__plus').style.display='';">清除</button>
            </div>
            <input type="text" class="img-field__input" name="{{ $name }}" value="{{ $value ?? '' }}"
                placeholder="上传后自动填写，也可手动粘贴地址">
            <span class="img-field__tip hint">点击图片或按钮选择本地文件</span>
        </div>
    </div>
</div>
