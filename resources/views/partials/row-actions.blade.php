@php
    $confirm = $confirm ?? 'Delete this record? This cannot be undone.';
@endphp
<div class="row-actions">
    @isset($view)
        <a class="btn btn-sm btn-outline-secondary" href="{{ $view }}" title="View"><i class="bi bi-eye"></i></a>
    @endisset
    @isset($edit)
        <a class="btn btn-sm btn-outline-primary" href="{{ $edit }}" title="Edit"><i class="bi bi-pencil"></i></a>
    @endisset
    @isset($pdf)
        <a class="btn btn-sm btn-outline-danger" href="{{ $pdf }}" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
    @endisset
    @isset($docx)
        <a class="btn btn-sm btn-outline-secondary" href="{{ $docx }}" title="Word"><i class="bi bi-file-earmark-word"></i></a>
    @endisset
    @isset($delete)
        <form method="POST" action="{{ $delete }}" onsubmit="return confirm(@json($confirm))">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
        </form>
    @endisset
</div>
