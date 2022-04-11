<div class="d-flex m-button">
    <button type="button" class="btn btn-info btn-sm mr-1"
            onclick="Main.editRecord('{{ route('web.users.update.form', $id) }}')"
    >
        <i class="fas fa-pencil-alt"></i>
    </button>

    <a
        onclick="Main.deleteDataTable('{{ route('web.users.delete', $id) }}')" href="javascript:void(0);"
       class="btn btn-sm btn-danger">
        <i class="fas fa-trash-alt"></i>
    </a>
</div>
