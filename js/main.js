var viewModel = {
    search: function (e) {
        $.get(
            $(e).attr('action'),
            $(e).serialize(),
            function (data) {
                $('.result').html(data);
            }
        );

        return false;
    }
};

ko.applyBindings(viewModel);