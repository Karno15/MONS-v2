function getCookie(name) {
    const cookieValue = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return cookieValue ? cookieValue.pop() : '';
}

function getPartyPokemon() {

    $.ajax({
        url: 'getPartyPokemon.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            var container = $('#pokemon-container');
            displayPokemon(response, container);
        },
        error: function (xhr, status, error) {
            console.error('Error fetching Pokemon data:', error);
        }
    });
}

function getBoxPokemon() {
    $.ajax({
        url: 'getBoxPokemon.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            var container = $('#box-container');
            displayPokemon(response, container);
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
        .append(`<div class="pokemon-stat"> ${pokemon.PokemonId}</b></div>`)
        .append(`<div class="pokemon-sprite"><img src="image.php?path=${spritesPath}${pokemon.PokedexId}.png" alt="sprite" /></div>`)
        .append(`<div class="pokemon-stat"><b>Level: ${pokemon.Level}</b></div>`);
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
        } else {
            moveName.text('-');
        }

        movesContainer.append(moveCard);
        moveCard.append(moveName);
        $('body').append(moveInfo);
        moveInfo.hide();

        (function (moveInfo) {
            moveName.hover(function () {
                var offset = $(this).offset();
                var height = $(this).outerHeight();
                var width = $(this).outerWidth();
                var moveInfoHeight = moveInfo.outerHeight();
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
    }

    return movesContainer;
}

function displayPokemon(data, container) {
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
    } else {
        alert('Please enter Pokemon ID and Exp');
    }
}

function getData(key, value) {
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
            return response;
        },
        function (xhr, status, error) {
            console.error('Error fetching data:', error);
            throw error;
        }
    );
}

$(document).ready(async function () {

    var token = await getCookie('token');

    function confirmAction(actionId, token, socket) {
        if (actionId) {
            const data = {
                type: 'confirm_action',
                actionId: actionId,
                token: token
            };
            console.log(data);
            socket.send(JSON.stringify(data));
            console.log('action ' + actionId + ' ok');
        }
    }

    function getUnfinishedAction() {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: 'getUnfinishedAction.php',
                type: 'GET',
                success: function (response) {
                    console.log(response);
                    resolve(response);
                },
                error: function (xhr, status, error) {
                    console.error(error);
                    reject(error);
                }
            });
        });
    }

    async function processNextTask(socket) {
        if (unfinishedTasks.length > 0) {
            const task = unfinishedTasks.shift();
            try {
                await handleMessage(task, token, socket);
            } catch (error) {
                console.error('Error processing task:', error);
            }
            processNextTask(socket);
        }
    }

    let modalQueue = []; // Queue to store messages

    function showModal(message, callback) {
        const modal = document.getElementById('modal');
        const modalMessage = document.getElementById('modal-message');
        const confirmButton = document.getElementById('modal-confirm-button');

        const modalData = { message, callback }; // Create modal data object
        modalQueue.push(modalData); // Add modal data to the queue

        if (modalQueue.length === 1) { // If this is the only message in the queue, show it
            displayNextModal();
        }

        function displayNextModal() {
            const nextModalData = modalQueue[0]; // Get the next modal data from the queue
            modalMessage.textContent = nextModalData.message;
            modal.style.display = 'block';

            confirmButton.onclick = function () {
                modal.style.display = 'none';
                modalQueue.shift(); // Remove the current message from the queue

                if (typeof nextModalData.callback === 'function') {
                    nextModalData.callback();
                }

                if (modalQueue.length > 0) { // If there are more messages in the queue, display the next one
                    displayNextModal();
                }
            };
        }
    }


    async function handleMessage(message, token, socket) {
        console.log('begin handling:' + message);
        getPartyPokemon();
        getBoxPokemon();

        console.log(message);

        var actionId = message.actionId ?? 0;

        if (message.pokemonId) {
            dataMon = await getData('pokemonId', message.pokemonId);
            pokemonName = dataMon[0].Name;
        }

        if (message.levelup) {
            console.log('Level Up!');
            showModal(pokemonName + ' grew to level ' + message.levelup + '!', function () {
                confirmAction(actionId, token, socket);

                if (message.expToAdd > 0) {
                    lastExpToAdd = message.expToAdd;
                    addEXP(message.pokemonId, message.expToAdd, token, socket);
                }
            });
        }

        if (message.learned && (Array.isArray(message.learned) ? message.learned.length > 0 : message.learned !== 0)) {
            let moveId;
            if (Array.isArray(message.learned)) {
                moveId = message.learned[0];
            } else {
                moveId = message.learned;
            }

            const dataMove = await getData('moveId', moveId);
            const moveName = dataMove[0].Name;

            showModal(pokemonName + ' learned ' + moveName + "!", function () {
                confirmAction(actionId, token, socket);

                if (message.expToAdd > 0) {
                    lastExpToAdd = message.expToAdd;
                    addEXP(message.pokemonId, message.expToAdd, token, socket);
                }
            });
        }


        if (message.moveSwap && (Array.isArray(message.moveSwap) ? message.moveSwap.length > 0 : message.moveSwap !== 0)) {
            moveId = message.moveSwap;
            let dataMove = await getData('moveId', moveId);
            let moveName = await dataMove[0].Name;
            console.log('new move');
            confirm(pokemonName + " is trying to learn " + moveName + ". " +
                "But, " + pokemonName + " can't learn more than four moves! " +
                "Delete an older move to make room for " + moveName + "?", async function (confirmed) {
                    if (confirmed) {
                        prompt('Which order?', async function (moveOrder) {
                            if (moveOrder !== null) {
                                const data = {
                                    type: 'learn_move',
                                    pokemonId: message.pokemonId,
                                    moveId: moveId,
                                    moveOrder: moveOrder,
                                    token: token
                                };
                                socket.send(JSON.stringify(data));
                                console.log("Move learned!");

                            } else {
                                console.log("Move learning was cancelled in the prompt.");

                            }
                        });
                    } else {
                        console.log("Cancelled!");
                        confirmAction(actionId, token, socket);
                    }
                });
        }

        if (message.evolve) {
            evolution = 1;
            dataDex = await getData('pokedexId', message.evolve);
            pokemonNewName = dataDex[0].Name;
        }

        if (evolution) {
            confirm(pokemonName + ' is evolving! Continue?', function (confirmed) {
                if (confirmed) {
                    const data = {
                        type: 'evolve_mon',
                        pokemonId: message.pokemonId,
                        evoType: 'EXP',
                        expToAdd: lastExpToAdd ?? 0,
                        token: token
                    };
                    socket.send(JSON.stringify(data));
                    lastExpToAdd = 0;
                    showModal(pokemonName + " evolved into " + pokemonNewName + "!", function () {
                    });
                } else {
                    showModal("Huh? " + pokemonName + " stopped evolving!", function () {
                    });
                }
                confirmAction(actionId, token, socket);
                evolution = 0;
                pokemonNewName = 0;
            });
        }

        getPartyPokemon();
        getBoxPokemon();
    }

    $("#editbutton").click(function () {
        $('#editbox').show();
    })

    $("#closeedit").click(function () {
        $('#editbox').hide();
    })

    $('#fileInput').on('change', function () {
        var fileName = $(this).val().split('\\').pop();
        $('#file').text(fileName);
    })

    var evolution = 0;
    let lastExpToAdd = 0;
    var confirmedActions = [];
    const socket = new WebSocket('ws://localhost:8080');


    socket.addEventListener('open', function (event) {
        console.log('Connected to the server');
        getUnfinishedAction().then(function (unfinishedData) {
            try {
                var data = JSON.parse(unfinishedData);
                if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                    unfinishedTasks = data.data;
                    processNextTask(socket);
                }
            } catch (error) {
                console.error('Error parsing unfinished action data:', error);
            }
        }).catch(function (error) {
            console.error('Error getting unfinished action:', error);
        });
    });

    socket.addEventListener('close', function (event) {
        console.log('Disconnected from the server');
    });

    socket.addEventListener('error', function (event) {
        console.error('WebSocket error:', event);
    });

    socket.addEventListener('message', async function (event) {
        var message = JSON.parse(event.data);
        handleMessage(message, token, socket);
    });

    $('#addExp').on('click', async function () {
        const pokemonId = $('#addexp-pokemonId').val();
        var exp = $('#addexp-exp').val();
        addEXP(pokemonId, exp, token, socket);
        getPartyPokemon();
        getBoxPokemon();
    });

    $('#addPokemon').click(async function () {
        const pokedexId = $('#addPokemon-PokedexId').val();
        const level = $('#addPokemon-level').val();

        if (pokedexId && level) {
            const data = {
                type: 'add_mon',
                pokedexId: pokedexId,
                level: level,
                token: token
            };
            socket.send(JSON.stringify(data));
            getPartyPokemon();
            getBoxPokemon();
        } else {
            alert('Please enter pokedex ID and level');
        }
    });

    $(document).on('click', '.release-btn', async function () {
        var pokemonId = $(this).data('pokemon-id');
        var confirmRelease = confirm("Are you sure you want to release this Pokémon?");
        if (confirmRelease) {
            var data = {
                type: 'release_pokemon',
                pokemonId: pokemonId,
                token: token
            };
            socket.send(JSON.stringify(data));
            getPartyPokemon();
            getBoxPokemon();
        }
    });


});
