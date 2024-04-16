function getPartyPokemon() {
    $.ajax({
        url: 'getPartyPokemon.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            displayPokemon(response);
        },
        error: function (xhr, status, error) {
            console.error('Error fetching Pokemon data:', error);
        }
    });
}

function createViewSpriteLevel(pokemon) {
    var spritesPath = 'sprites/pkfront/';

    return $('<div></div>')
        .append(`<div class="pokemon-name" class="pokemon-view">${pokemon.Name}</div>`)
        .append(`<div class="pokemon-sprite"><img src="image.php?path=${spritesPath}${pokemon.PokedexId}.png" alt="sprite" /></div>`)
        .append(`<div class="pokemon-stat"><b>Level: ${pokemon.Level}</b></div>`);
}

function createHPView(pokemon) {
    var percentageHP = Math.ceil((pokemon.HPLeft / pokemon.HP) * 100);
    var color = '#ff0000';
    var statusColor = getStatusColor(pokemon.Status);
    pokemon.Status = (pokemon.Status == 'OK') ? '' : pokemon.Status;
    if (percentageHP > 25) color = '#ffb700';
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

function displayPokemon(data) {
    var container = $('#pokemon-container');

    container.empty();

    data.forEach(function (pokemon) {
        var card = $('<div class="pokemon-card"></div>');
        card.append(createViewSpriteLevel(pokemon));
        card.append(createHPView(pokemon));
        card.append(createStatsView(pokemon));
        card.append(createExpView(pokemon));
        container.append(card);
    });
}


function addPokemon(pokedexId, level) {
    $.ajax({
        url: 'addPokemon.php',
        method: 'POST',
        data: {
            pokedexId: pokedexId,
            level: level
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                getPartyPokemon();
            } else {
                alert('Failed to add Pokemon.');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error adding Pokemon:', error);
        }
    });
}

function addExp(pokemonId, exp) {
    defeat();
    $.ajax({
        url: 'addExp.php',
        method: 'POST',
        data: {
            pokemonId: pokemonId,
            exp: exp
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                getPartyPokemon();
            } else {
                alert('Failed to add Exp.');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error adding Exp:', error);
        }
    });
}
function defeat() {
    var userId = '<?php echo json_encode($_SESSION["userid"]); ?>';

    document.getElementById('test').innerHTML = userId;
}