$(document).ready(function($) {
    // Переинициализация при открытии модального окна
    $('body').on('shown.bs.modal', '.modal', function() {
        Main.init();
    });
    // Инициализация инпутов для фильтров
    Main.init();
});


var Main = {
    // Базовые настройки для Datatables
    commonConfigDt: {
        pageLength: 50,
        processing: true,
        serverSide: true,
        order: false,
        // language: {
        //     processing:         Lang.get('datatables.processing'),
        //     search:             Lang.get('datatables.search'),
        //     lengthMenu:         Lang.get('datatables.length_menu'),
        //     info:               Lang.get('datatables.info'),
        //     infoEmpty:          "",
        //     infoFiltered:       "",
        //     infoPostFix:        "",
        //     loadingRecords:     Lang.get('datatables.loading_records'),
        //     zeroRecords:        "",
        //     emptyTable:         "",
        //     paginate: {
        //         first:          Lang.get('datatables.paginate.first'),
        //         previous:       Lang.get('datatables.paginate.previous'),
        //         next:           Lang.get('datatables.paginate.next'),
        //         last:           Lang.get('datatables.paginate.last'),
        //     },
        //     aria: {
        //         sortAscending:  Lang.get('datatables.aria.sort_ascending'),
        //         sortDescending: Lang.get('datatables.aria.sort_descending'),
        //     }
        // },
        scrollX: true,
        stateSave: true,
    },

    confDrp: {
        "format": "DD.MM.YYYY",
        "formatDateTime": "DD.MM.YYYY HH:mm",
        "separator": " - ",
        "applyLabel": "Применить",
        "cancelLabel": "Отменить",
        "fromLabel": "От",
        "toLabel": "До",
        "customRangeLabel": "Custom",
        "weekLabel": "W",
        "daysOfWeek": [
            "Вс",
            "Пн",
            "Вт",
            "Ср",
            "Чт",
            "Пт",
            "Сб"
        ],
        "monthNames": [
            "Январь",
            "Февраль",
            "Март",
            "Апрель",
            "Май",
            "Июнь",
            "Июль",
            "Август",
            "Сентябрь",
            "Октябрь",
            "Ноябрь",
            "Декабрь"
        ],
        "firstDay": 1
    },

    init: function () {
        this.initDefaultSelect2();
        DatetimepickerHelper.datePickerInit();
        DatetimepickerHelper.dateTimePickerInit();
        DatetimepickerHelper.dateTimesRangePickerInit();
        this.summernote();
        InputMaskHelper.float();
    },

    summernote: function (element = '.summernote') {
        $(element).summernote({
            minHeight: 150,
            lang: 'ru-RU',
            disableDragAndDrop: true,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
            ],
            callbacks: {
                onKeyup: function(e) {
                    $(this).closest('.form-group').find('.validation-maxlength').html($(this).val().length + '/' + $(this).attr('maxLength'));
                }
            }
        });
    },

    searchDataTableThrottle: $.fn.dataTable.util.throttle(
        function (table, input) {
            table.search($(input).val()).draw();
        },
        1500
    ),

    // Поиск в таблице
    searchDataTable: function(table, input){
        if(table && input){
            this.searchDataTableThrottle(table, input);
        }
        return true;
    },

    updateDataTable: function(e, table){
        if(!table){
            table = window.LaravelDataTables["dtListElements"];
        }
        table.draw();
        e.preventDefault();
    },

    // Удаление элемента
    deleteDataTable: function (route) {
        if (confirm('Собираетесь удалить элемент из системы?')) {

            let data = new FormData();
            data.append('_token', $('meta[name="csrf-token"]').attr('content'));
            data.append('_method', 'DELETE');

            let success = function (data) {
                if (data['action'] && data['action'] === 'reload_table') {
                    dtListelements.ajax.reload(null, false);
                    toastr.error('Удалено');
                }
            };

            let error = function(data) {
                if (data.status === 403) {
                    toastr.error('Недостаточно прав')
                }
            }

            this.ajaxRequest('POST', route, data, success, error);
        }
    },

    // Подстановка в строки таблицы ссылок на просмотр элемента. Требуется скрытая ссылка с классом data-table-show и роутом
    showElementDataTable: function(table) {
        $('#'+ table +' tr').each(function(i,e) {
            $(e).children('td').click(function() {
                if($(this).find("a,input,select,button,img").length == 0) {
                    let atrr = $(e).find('.data-table-show').closest('a');
                    location.href = atrr.attr('href');
                }
            });
        });
    },

    showModalElementDataTable: function(table) {
        $('#'+ table +' tr').each(function(i,e) {
            $(e).children('td').click(function() {
                if($(this).find("a,input,select,button,img").length == 0) {
                    let attr = $(e).find('.data-table-show').closest('a');
                    attr.trigger('click');
                }
            });
        });
    },

    editElementDataTable: function(table) {
        $('#'+ table +' tr').each(function(i,e) {
            $(e).children('td').click(function() {
                if($(this).find("a,input,select,button,img").length == 0) {
                    let atrr = $(e).find('.data-table-edit').closest('a');
                    location.href = atrr.attr('href');
                }
            });
        });
    },

    // Отправка ajax запроса на сервер
    ajaxRequest: function(typeRequest, urlRequest, dataRequest, successFunction, errorFunction, returnFunction){
        if(typeRequest && urlRequest && typeof dataRequest == 'object' && successFunction){
            let request = $.ajax({
                type   		: typeRequest,
                url    		: urlRequest,
                cache  		: false,
                data 	  	: dataRequest,
                processData	: false,
                contentType	: false,
                success 	: successFunction,
                error		: errorFunction,
                beforeSend: function () {
                    let button = $('form').find('.modal-button-form');

                    // Отключаем кнопку отправки для избежания повторного нажатия
                    if (button) {
                        let spinner = '<span class="spinner-grow spinner-grow-sm mr-2" role="status" aria-hidden="true"></span>';
                        button.prepend(spinner).prop('disabled', true);
                    }
                },
                complete: function () {
                    let button = $('form').find('.modal-button-form');

                    // Включаем кнопку отправки для избежания повторного нажатия
                    if (button) {
                        button.find('.spinner-grow').remove();
                        button.prop('disabled', false);
                    }
                },
            });
            if(returnFunction === true)
                return request;
            else
                return true;
        }
        return false;
    },

    destroyElement: null,

    // Снятие всех фильтров
    dropFilter: function () {
        $('[data-name="filter"]').val('');
        $('input[type="checkbox"][data-name="filter"]').prop("checked", false);
        $('[data-name="filter"]').trigger('change');
    },

    // Открытие модального окна формы создания записи
    createRecord: function (route) {
        let data = {
            _method: 'GET',
        };
        let success = function (data) {
            if (data.action == 'success') {
                let html = $.parseHTML(data.html);
                $( "#create" ).remove();
                $('#form').find(".modal-header").after(html);
                $('#form').find('.modal-title-form').text(data.title);
                $('#form').find('.modal-button-form').text(data.button);
                // $.getScript( "/js/main.js" )
                $('#form').modal('show');
            }
        };

        let error = function(data) {
            if (data.status === 403) {
                toastr.error('Недостаточно прав')
            }
        }

        this.ajaxRequest('GET', route, data, success, error);
    },

    // Открытие модального окна формы редактирования записи
    editRecord: function (route) {
        let data = {
            _method: 'GET',
        };

        let success = function (data) {
            if (data.action == 'success') {
                let html = $.parseHTML(data.html);
                $( "#create" ).remove();
                $('#form').find(".modal-header").after(html);
                $('#form').find('.modal-title-form').text(data.title);
                $('#form').find('.modal-button-form').text(data.button);
                // $.getScript( "/js/main.js" )
                $('#form').modal('show');
            }
        };

        let error = function(data) {
            if (data.status === 403) {
                toastr.error('Недостаточно прав')
            }
        }

        this.ajaxRequest('GET', route, data, success, error);
    },

    // Обновление записи в базе данных
    updateRecord: function (route) {
        event.preventDefault();

        let data = new FormData($('#create')[0]);
        data.append('_method', 'PATCH');
        let success = function (data) {
            if (data.action == 'reload_table') {
                toastr.success(data['success']);
                if (typeof(dtListelements) != "undefined") {
                    dtListelements.ajax.reload(null, false);
                }
                $('#form').modal('hide');
                $('#create').remove();
            }
        };

        let error = function (data) {
            $.each(data.responseJSON.errors, function (index, error) {
                toastr.error(error[0]);
            });

            if (data.status && data.status === 403) {
                toastr.error('Недостаточно прав')
            }
        };

        this.ajaxRequest('POST', route, data, success, error);
    },

    // Создание записи в базе данных
    storeRecord: function (route) {
        event.preventDefault();

        let data = new FormData($('#create')[0]);
        let success = function (data) {
            toastr.success(data['success']);
            if (data.action == 'reload_table') {
                dtListelements.ajax.reload(null, false);
            }
            $('#form').modal('hide');
            $('#create').remove();
        };

        let error = function (data) {
            $.each(data.responseJSON.errors, function (index, error) {
                toastr.error(error[0]);
            });

            if (data.status && data.status === 403) {
                toastr.error('Недостаточно прав')
            }
        };

        this.ajaxRequest('POST', route, data, success, error);
    },

    // Скрытие модального окна с удалением формы внутри него
    dissmissModal: function (id) {
        $(id).modal('hide');
        $(id).find('form').remove();
    },

    disableToggleElement: function(id) {
        let element = document.querySelector('#' + id);
        element.toggleAttribute("disabled");
    },

    tagSelect2: function (params) {
        const term = $.trim(params.term);
        if (term === '') {
            return null;
        }
        return {
            id: term,
            text: term,
            newTag: true
        }
    },

    initSelect2: function(dataName, route, data){
        $('[data-name="' + dataName + '"]').select2({
            'ajax': {
                url: route,
                dataType: 'json',
                type: 'POST',
                cache: true,
                data: function (params) {
                    return {
                        data,
                        'string': params.term,
                        'page': params.page || 1,
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                    };
                },
            },
            'allowClear': true,
            'createTag': this.tagSelect2,
            'language': Main.autocompleteLanguageConfig,
            'minimumInputLength': '0',
            'placeholder': "Ничего не выбрано",
            'width': '100%',
            'templateResult': Main.formatStateSelect2
        });
    },

    formatStateSelect2: function (state) {
        if (!state.image) { return state.text; }
        return $('<span ><img class="select2-image" src="' + state.image + '" /> ' + state.text + '</span>');
    },
    initDefaultSelect2: function () {
        $('.select2').select2({
            'placeholder': "Ничего не выбрано",
            'allowClear': true,
            'width': '100%'
        });
    },

    autocompleteLanguageConfig: {
        errorLoading: function () {
            return 'Результат не может быть загружен.';
        },
        inputTooLong: function (args) {
            const overChars = args.input.length - args.maximum;
            let message = 'Пожалуйста, удалите ' + overChars + ' символ';
            if (overChars >= 2 && overChars <= 4) {
                message += 'а';
            } else if (overChars >= 5) {
                message += 'ов';
            }
            return message;
        },
        inputTooShort: function (args) {
            let remainingChars = args.minimum - args.input.length;

            return 'Пожалуйста, введите ' + remainingChars + ' или более символов';
        },
        loadingMore: function () {
            return 'Загружаем ещё ресурсы…';
        },
        maximumSelected: function (args) {
            let message = 'Вы можете выбрать ' + args.maximum + ' элемент';

            if (args.maximum  >= 2 && args.maximum <= 4) {
                message += 'а';
            } else if (args.maximum >= 5) {
                message += 'ов';
            }

            return message;
        },
        noResults: function () {
            return 'Ничего не найдено';
        },
        searching: function () {
            return 'Поиск…';
        }
    },
};

var DatetimepickerHelper = {
    dateTimePickerInit: function() {
        $('[datetimepicker]').daterangepicker({
            'opens': 'right',
            "timePicker": true,
            "timePicker24Hour": true,
            'locale': Main.confDrp,
            'singleDatePicker' : true,
            'showDropdowns' : true,
            'autoUpdateInput': false
        }, function(start_date, end_date, label) {
            this.element.val(start_date.format(Main.confDrp.formatDateTime)).change();
        });

        // Маска полного времени
       InputMaskHelper.DDMMYYYYHm();
    },
    datePickerInit: function () {
        // Инициализация Datepicker
        $('[datepicker]').daterangepicker({
            'opens': 'right',
            'locale': Main.confDrp,
            'singleDatePicker' : true,
            'showDropdowns' : true,
            'autoUpdateInput': true
        }, function(start_date, end_date) {
            this.element.val(start_date.format(Main.confDrp.format)).change();
        });
        InputMaskHelper.DDMMYYYY();
    },
    dateRangePickerInit: function () {
        // Инициализация Daterangepicker
        $('[daterangepicker]').daterangepicker({
            'opens': 'right',
            'locale': Main.confDrp,
            'autoUpdateInput': false
        }, function(start_date, end_date) {
            this.element.val(start_date.format(Main.confDrp.format) + ' - ' + end_date.format(Main.confDrp.format)).change();
        });
    },
    dateTimesRangePickerInit: function () {
        $('[dateTimesRangepicker]').daterangepicker({
            timePicker: true,
            timePicker24Hour: true,
            'opens': 'right',
            'locale': Main.confDrp,
            'autoUpdateInput': false
        }, function(start_date, end_date) {
            this.element.val(start_date.format(Main.confDrp.formatDateTime) + ' - ' + end_date.format(Main.confDrp.formatDateTime)).change();
        });
    },
}

var InputMaskHelper = {
    DDMMYYYY: function () {
        $('[data-inputmask]').inputmask("99.99.9999");
    },

    DDMMYYYYHm: function () {
        $('[data-timeinputmask]').inputmask("99.99.9999 99:99");
    },

    float: function () {
        $('[data-float-inputmask]').inputmask("9{1,3}.9{0,2}");
    }
};
