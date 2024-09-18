function setCookie(name, value) {
    document.cookie = name + "=" + (value || "") + "; path=/";
}

function getCookie(name) {
    const cookieValue = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return cookieValue ? cookieValue.pop() : '';
}

function eraseCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
}

function showLoadingCircle(text) {
    $('.loading-info').text(text);
    $('#loading-overlay').stop(true, true).fadeIn(100);
    loadingVisible = true;
}

function hideLoadingCircle() {
    $('#loading-overlay').stop(true, true).fadeOut(100);
    loadingVisible = false;
}

function getPartyPokemon(callback) {
    showLoadingCircle('Loading Pokemon...');
    $.ajax({
        url: 'getPartyPokemon.php',
        method: 'GET',
        success: function (response) {
            if (callback) {
                callback(response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching Pokemon data:', error);
        }
    });
}


function getBoxPokemon() {
    showLoadingCircle('Loading Pokemon...')
    $.ajax({
        url: 'getBoxPokemon.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            var container = $('#box-container');
            displayPokemonParty(response, container);
            return response;
        },
        error: function (xhr, status, error) {
            console.error('Error fetching Pokemon data:', error);
        }
    });
}

function createViewSpriteLevel(pokemon) {
    var spritesPath = 'sprites/pkfront/';

    var container = $('<div></div>');
    var pokemonName = $(`<div class="pokemon-name" class="pokemon-view">${pokemon.Name}</div>`);
    var pokemonStatId = $(`<div class="pokemon-stat"> ${pokemon.PokemonId}</div>`);
    var pokemonSpriteContainer = $(`<div class="pokemon-sprite"></div>`);
    var pokemonStatLevel = $(`<div class="pokemon-stat"><b>Level: ${pokemon.Level}</b></div>`);

    var pokemonSprite = $(`<img src="image.php?path=${spritesPath}${pokemon.PokedexId}.png" alt="sprite-${pokemon.Oname}" title=${pokemon.Oname} />`);

    showLoadingCircle('Loading Pokemon...');

    pokemonSprite.on('load', function () {
        hideLoadingCircle();
    });

    pokemonSprite.on('error', function () {
        hideLoadingCircle();
        console.error('Error loading image:', pokemonSprite.attr('src'));
    });

    pokemonSpriteContainer.append(pokemonSprite);
    container.append(pokemonName, pokemonStatId, pokemonSpriteContainer, pokemonStatLevel);

    return container;
}

function createHPView(pokemon) {
    var percentageHP = Math.ceil((pokemon.HPLeft / pokemon.HP) * 100);
    var color = '#ff0000';
    var statusColor = getStatusColor(pokemon.Status);
    pokemon.Status = (pokemon.Status == 'OK') ? '' : pokemon.Status;
    if (percentageHP > 20) color = '#ffb700';
    if (percentageHP > 50) color = '#4caf50';
    return $(`<div>
                <div class="hpcount pokemon-stat">HP: ${pokemon.HPLeft}/${pokemon.HP}</div>
                <div class="progress-bar">
                    <div class="progress-hp" style="width: ${percentageHP}%; background-color: ${color};"></div>
                </div>
                <div class='status pokemon-stat' style="background-color: ${statusColor};">${pokemon.Status}</div>
            </div>`);
}


function createStatsView(pokemon) {
    return $('<div class="pokemon-stats"></div>')
        .append(`<div class="pokemon-stat">Attack: ${pokemon.Attack}</div>`)
        .append(`<div class="pokemon-stat">Defense: ${pokemon.Defense}</div>`)
        .append(`<div class="pokemon-stat">Special Atk: ${pokemon.SpAtk}</div>`)
        .append(`<div class="pokemon-stat">Special Def: ${pokemon.SpDef}</div>`)
        .append(`<div class="pokemon-stat">Speed: ${pokemon.Speed}</div>`);
}

function createExpView(pokemon) {
    var expleft = pokemon.ExpTNL - (pokemon.Exp - pokemon.MinExp);
    var percentageExp = Math.ceil(((pokemon.Exp - pokemon.MinExp) / pokemon.ExpTNL) * 100);
    return $(`<br><div class="pokemon-stat">EXP left: ${expleft ? expleft : '0'}</div>
                <div class="progress-bar">
                    <div class="progress-exp" style="width: ${percentageExp}%;"></div>
                </div>`);
}

function getStatusColor(status) {
    var statusColorMap = {
        'PAR': 'yellow',
        'BRN': 'red',
        'PSN': 'purple',
        'FRZ': 'lightblue',
        'FNT': 'gray',
        'OK': 'transparent'
    };
    return statusColorMap[status] || 'transparent';
}

function createMovesView(pokemon) {
    var movesContainer = $('<div class="moves-container"></div>');

    for (var i = 0; i < 4; i++) {
        var moveCard = $('<div class="move-card"></div>');
        var moveName = $('<div class="move-name"></div>');
        var moveDescription = $('<div class="move-description"></div>');
        var moveInfo = $('<div class="move-info"></div>');

        if (pokemon.Moves[i]) {
            var move = pokemon.Moves[i];
            moveName.text(move.Name);
            moveDescription.text(move.Description);
            var moveEffect = $('<div></div>').text(move.Effect);
            var movePP = $('<div></div>').text('PP: ' + move.PPValue + "/" + move.PP);
            var movePower = $('<div></div>').text('Power: ' + (move.Power ? move.Power : '-'));
            var moveAccuracy = $('<div></div>').text('Accuracy: ' + move.Accuracy);
            var moveType = $('<div></div>').text(move.TypeName);

            moveType.css({
                'background-color': '#' + move.TypeColor,
                'padding': '3px 8px',
                'border-radius': '3px',
                'display': 'inline-block'
            });

            moveInfo.append(moveType, moveEffect, movePP, movePower, moveAccuracy, moveDescription);

            (function (moveInfo) {
                moveName.hover(function () {
                    var offset = $(this).offset();
                    var height = $(this).outerHeight();
                    var width = $(this).outerWidth();
                    var moveInfoWidth = moveInfo.outerWidth();
                    moveInfo.css({
                        top: offset.top + height + 10,
                        left: offset.left - moveInfoWidth / 2 + width / 2
                    });
                    moveInfo.show();
                }, function () {
                    moveInfo.hide();
                });
            })(moveInfo);
        } else {
            moveName.text('-');
        }

        movesContainer.append(moveCard);
        moveCard.append(moveName);
        $('body').append(moveInfo);
        moveInfo.hide();
    }

    return movesContainer;
}

function generateMoveInfoHtml(move) {
    var moveDescription = $('<div></div>').addClass('move-description').text(move.Description);
    var moveEffect = $('<div></div>').addClass('move-effect').text('Effect: ' + move.Effect);
    var movePP = $('<div></div>').addClass('move-pp').text('PP: ' + move.PP + "/" + move.PP);
    var movePower = $('<div></div>').addClass('move-power').text('Power: ' + (move.Power ? move.Power : '-'));
    var moveAccuracy = $('<div></div>').addClass('move-accuracy').text('Accuracy: ' + move.Accuracy);
    var moveType = $('<div></div>').addClass('move-type').text('Type: ' + move.TypeName);

    moveType.css({
        'background-color': '#' + move.TypeColor,
        'padding': '3px 8px',
        'border-radius': '3px',
        'display': 'inline-block'
    });

    var moveInfoContainer = $('<div></div>').addClass('move-info-popup')
        .append(moveDescription)
        .append(moveEffect)
        .append(movePP)
        .append(movePower)
        .append(moveAccuracy)
        .append(moveType);


    return moveInfoContainer;
}

function displayMon(data, container) {
    container.empty();

    if (!Array.isArray(data)) {
        return;
    }

    data.forEach(function (pokemon) {
        var card = $('<div class="pokemon-card"></div>');
        card.append(createViewSpriteLevel(pokemon));
        card.append(createHPView(pokemon));
        container.append(card);
    });
}

function displayPokemonParty(data, container) {
    container.empty();

    if (!Array.isArray(data)) {
        return;
    }

    data.forEach(function (pokemon) {
        var card = $('<div class="pokemon-card"></div>');
        card.append(createViewSpriteLevel(pokemon));
        card.append(createHPView(pokemon));
        card.append(createStatsView(pokemon));
        card.append(createExpView(pokemon));
        if (pokemon.Moves) {
            card.append(createMovesView(pokemon));
        }
        card.append('<button class="release-btn" data-pokemon-id=' + pokemon.PokemonId + '>Release</button>');
        container.append(card);
    });
}

function addEXP(pokemonId, exp, token, socket) {
    if (pokemonId && exp) {
        const data = {
            type: 'grant_exp',
            pokemonId: pokemonId,
            exp: exp,
            token: token
        };
        socket.send(JSON.stringify(data));
    }
}

function getData(key, value) {
    showLoadingCircle('Loading data...')
    return $.ajax({
        url: 'getData.php',
        method: 'POST',
        data: {
            key: key,
            value: value
        },
        dataType: 'json'
    }).then(
        function (response) {
            hideLoadingCircle();
            return response;
        },
        function (xhr, status, error) {
            console.error('Error fetching data:', error);
            throw error;
        }
    );
}
