var Diary = {
    storeRecord: function (route, date) {
        event.preventDefault();

        var dateValue = $('input[name="' + date + '"]').val();

        let data = new FormData();
        data.append('date', dateValue);
        data.append('_token', $('input[name="_token"]').val());

        let success = function (data) {
            toastr.success(data['success']);
        };

        let error = function (data) {
            toastr.error(data.responseJSON.error);
            if (data.status && data.status === 403) {
                toastr.error('Недостаточно прав')
            }
        };

        Main.ajaxRequest('POST', route, data, success, error);
    },
}
