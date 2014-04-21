var viewModel = {
    heroes: ko.observableArray([]),
    aliases: ko.observableArray([]),
    privateCount: ko.observable(),

    showLoader: ko.observable(false),
    showResults: ko.observable(false),
    showResultNotification: ko.observable(false),
    showResultDetails: ko.observable(false),

    heroData: [],

    search: function (e) {
        viewModel.showLoader(true);
        viewModel.showResults(false);
        viewModel.showResultDetails(false);
        viewModel.showResultNotification(false);

        $.get(
            $(e).attr('action'),
            $(e).serialize(),

            function (data) {
                var resultsExist = data.aliases.length > 0;

                viewModel.showResults(true);
                viewModel.showLoader(false);
                viewModel.showResultDetails(resultsExist);
                viewModel.showResultNotification(!resultsExist);

                viewModel.privateCount(data.privateCount);
                viewModel.aliases(data.aliases);

                viewModel.heroes([]);
                $.each(data.heroes, function (id, useCount) {
                    viewModel.heroes.push({
                        name: viewModel.heroData[id].fullName,
                        useCount: useCount,
                        thumbSrc: getThumbByHeroName(
                            viewModel.heroData[id].name
                        )
                    });
                });

                // Sort heroes in descending order by use count
                viewModel.heroes.sort(function (left, right) {
                    return left.useCount === right.useCount ? 0:
                        (left.useCount > right.useCount ? -1 : 1);
                });
            }
        );

        return false;
    }
};

function getThumbByHeroName(name) {
    return 'http://media.steampowered.com/apps/dota2/images/heroes/' +
        name + '_sb.png';
}

// Get hero name list from dota2-api
$.get(
    'lib/vendor/dota2-api/data/heroes.json',
    {},
    function (data) {
        $.each(data.heroes, function (index, hero) {
            viewModel.heroData[hero.id] = {
                name: hero.name,
                fullName: hero.localized_name
            };
        });
    }
);

ko.applyBindings(viewModel);