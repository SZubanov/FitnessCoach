<form id="create" method="POST" enctype="multipart/form-data" role="form">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Дата<sup class="text-danger">*</sup></label>
                    <input name="date" type="text" maxlength="255" class="form-control" required datepicker data-inputmask
                           value="{{ isset($diary) ? $diary->date ?? '' : old('date') }}"
                    >
                </div>
                <div class="form-group">
                    <label>Ккал<sup class="text-danger">*</sup></label>
                    <input name="kcal" type="text" maxlength="255" class="form-control" required
                           value="{{ isset($diary) ? $diary->kcal ?? '' : old('kcal') }}"
                    >
                </div>
                <div class="form-group">
                    <label>Белки<sup class="text-danger">*</sup></label>
                    <input name="protein" type="text" maxlength="255" class="form-control" required
                           value="{{ isset($diary) ? $diary->protein ?? '' : old('protein') }}"
                    >
                </div>
                <div class="form-group">
                    <label>Жиры<sup class="text-danger">*</sup></label>
                    <input name="fat" type="text" maxlength="255" class="form-control" required
                           value="{{ isset($diary) ? $diary->fat ?? '' : old('fat') }}"
                    >
                </div>
                <div class="form-group">
                    <label>Углеводы<sup class="text-danger">*</sup></label>
                    <input name="carbs" type="text" maxlength="255" class="form-control" required
                           value="{{ isset($diary) ? $diary->carbs ?? '' : old('carbs') }}"
                    >
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-success modal-button-form"
                @if(isset($diary)) onclick="Main.updateRecord('{{ route('web.admin.users.update', $diary->id) }}')"
                @else onclick="Main.storeRecord('{{ route('web.users.diary.store.macros') }}')" @endif
        >Создать
        </button>
        <button type="button" class="btn btn-danger" onclick="Main.dissmissModal('#form')">Отмена</button>
    </div>
</form>
