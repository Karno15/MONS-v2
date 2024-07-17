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
        return new Promise((resolve, reject) => {
            pendingTasks.push(actionId);
            const data = {
                type: 'confirm_action',
                actionId: actionId,
                token: token
            };
            socket.send(JSON.stringify(data));
            socket.addEventListener('message', function onConfirm(event) {
                const message = JSON.parse(event.data);
                if (message.responseFrom === 'confirm_action' && message.actionId === actionId) {
                    socket.removeEventListener('message', onConfirm);
                    const index = pendingTasks.indexOf(actionId);
                    if (index > -1) {
                        pendingTasks.splice(index, 1);
                    }
                    resolve(true);
                }
            });
        });
    }

    function getUnfinishedAction() {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: 'getUnfinishedAction.php',
                type: 'GET',
                success: function (response) {
                    message = JSON.parse(response);
                    console.log('Unfinished actions from ajax:')
                    console.log(message);
                    resolve(response);
                },
                error: function (xhr, status, error) {
                    console.error(error);
                    reject(error);
                }
            });
        });
    }

    let pendingTasks = [];
    let modalQueue = [];
    let isModalShowing = false;
    let isHandlingMessage = false;


    async function processNextTask(socket) {
        if (!isHandlingMessage) {
            isHandlingMessage = true;

            //  await fetchUnfinishedTasks();

            while (unfinishedTasks.length > 0) {
                const nonEvolveTasks = unfinishedTasks.filter(task => !task.evolve);
                const evolveTasks = unfinishedTasks.filter(task => task.evolve);

                unfinishedTasks = [...nonEvolveTasks, ...evolveTasks];

                const task = unfinishedTasks.shift();
                try {
                    await handleMessage(task, token, socket);
                } catch (error) {
                    console.error('Error processing task:', error);
                }
                //location.reload()
            }

            isHandlingMessage = false;
        }
    }

    async function fetchUnfinishedTasks() {
        try {
            const unfinishedData = await getUnfinishedAction();
            const data = JSON.parse(unfinishedData);
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                unfinishedTasks = data.data;
            } else {
                unfinishedTasks = [];
            }
        } catch (error) {
            console.error('Error fetching unfinished tasks:', error);
            unfinishedTasks = [];
        }
    }

    function showModal(message, callback) {
        const modalData = { type: 'alert', message, callback };
        addToModalQueue(modalData);
    }

    function showConfirmModal(message, callback) {
        const modalData = { type: 'confirm', message, callback };
        addToModalQueue(modalData);
    }

    function showOptionModal(message, options, callback) {
        const modalData = {
            type: 'option',
            message: message,
            options: options,
            callback: callback
        };
        addToModalQueue(modalData);
    }

    function showPromptModal(message, callback) {
        const modalData = { type: 'prompt', message, callback };
        addToModalQueue(modalData);
    }

    function addToModalQueue(modalData) {
        if (!isMessageInQueue(modalData)) {
            modalQueue.push(modalData);
        }

        if (!isModalShowing) {
            displayNextModal();
        }
    }

    function isMessageInQueue(newMessage) {
        return modalQueue.some(item => item.message === newMessage.message);
    }

    function displayNextModal() {
        if (modalQueue.length === 0) {
            isModalShowing = false;
            return;
        }

        isModalShowing = true;
        const nextModalData = modalQueue.shift();

        switch (nextModalData.type) {
            case 'alert':
                displayAlertModal(nextModalData);
                break;
            case 'confirm':
                displayConfirmModal(nextModalData);
                break;
            case 'option':
                displayOptionModal(nextModalData);
                break;
            case 'prompt':
                displayPromptModal(nextModalData);
                break;
        }
    }

    function displayAlertModal(data) {
        const modal = $('#modal');
        const modalMessage = $('#modal-message');
        const confirmButton = $('#modal-confirm-button');

        $('.move-info').hide();

        modalMessage.text(data.message);
        modal.css('display', 'block');

        confirmButton.on('click', function () {
            modal.css('display', 'none');
            if (typeof data.callback === 'function') {
                data.callback();
            }
            isModalShowing = false;
            displayNextModal();
        });
    }

    function displayConfirmModal(data) {
        const modal = document.getElementById('confirm-modal');
        const modalMessage = document.getElementById('confirm-message');
        const yesButton = document.getElementById('confirm-yes-button');
        const noButton = document.getElementById('confirm-no-button');

        $('.move-info').hide();

        modalMessage.textContent = data.message;
        modal.style.display = 'block';

        yesButton.onclick = function () {
            modal.style.display = 'none';
            if (typeof data.callback === 'function') {
                data.callback(true);
            }
            isModalShowing = false;
            displayNextModal();
        };

        noButton.onclick = function () {
            modal.style.display = 'none';
            if (typeof data.callback === 'function') {
                data.callback(false);
            }
            isModalShowing = false;
            displayNextModal();
        };
    }


    function displayOptionModal(data) {

        const $modal = $('#option-modal');
        const $modalMessage = $('#option-message');
        const $optionList = $('#option-list');
        const $okButton = $('#option-confirm-button');
        const $cancelButton = $('#option-cancel-button');

        $modalMessage.text(data.message);
        $optionList.empty();

        data.options.forEach(option => {
            const $optionButton = $('<button class="option-button"></button>').text(option).click(function () {
                $('.option-button').removeClass('selected');
                $(this).addClass('selected');
            });
            $optionList.append($optionButton);
        });

        $modal.show();

        $okButton.off('click').on('click', function () {
            const selectedOption = $('.option-button.selected').text();
            $modal.hide();
            if (typeof data.callback === 'function') {
                data.callback(selectedOption);
            }
            isModalShowing = false;
            displayNextModal();
        });

        $cancelButton.off('click').on('click', function () {
            $modal.hide();
            if (typeof data.callback === 'function') {
                data.callback(null);
            }
            isModalShowing = false;
            displayNextModal();
        });
    }

    function displayPromptModal(data) {

        $('.move-info').hide();

        const $modal = $('#prompt-modal');
        const $modalMessage = $('#prompt-message');
        const $promptInput = $('#prompt-input');
        const $okButton = $('#prompt-confirm-button');
        const $cancelButton = $('#prompt-cancel-button');

        $modalMessage.text(data.message);
        $modal.show();

        $okButton.off('click').on('click', function () {
            $modal.hide();
            if (typeof data.callback === 'function') {
                data.callback($promptInput.val());
            }
            isModalShowing = false;
            displayNextModal();
        });

        $cancelButton.off('click').on('click', function () {
            $modal.hide();
            if (typeof data.callback === 'function') {
                data.callback(null);
            }
            isModalShowing = false;
            displayNextModal();
        });
    }

    var lastExpToAdd = 0;
    async function handleMessage(message, token, socket) {
        return new Promise(async (resolve, reject) => {
            try {
                console.log('begin handling');
                console.log(message);
                const actionId = message.actionId ?? 0;
                let pokemonName = '';
                let pokemonNewName = '';

                if (message.pokemonId) {
                    const dataMon = await getData('pokemonId', message.pokemonId);
                    pokemonName = dataMon[0].Name;
                }

                if (message.levelup) {
                    console.log('Level Up!');
                    await showModalPromise(`${pokemonName} grew to level ${message.levelup}!`);
                    await confirmAction(actionId, token, socket);

                    if (message.expToAdd > 0) {
                        lastExpToAdd = message.expToAdd;
                        await addEXP(message.pokemonId, message.expToAdd, token, socket);
                    }
                }

                if (message.learned && (Array.isArray(message.learned) ? message.learned.length > 0 : message.learned !== 0)) {
                    let moveId = Array.isArray(message.learned) ? message.learned[0] : message.learned;
                    const dataMove = await getData('moveId', moveId);
                    const moveName = dataMove[0].Name;

                    await showModalPromise(`${pokemonName} learned ${moveName}!`);
                    await confirmAction(actionId, token, socket);
                }

                if (message.moveSwap && (Array.isArray(message.moveSwap) ? message.moveSwap.length > 0 : message.moveSwap !== 0)) {
                    const moveId = message.moveSwap;
                    const dataMove = await getData('moveId', moveId);
                    const moveName = dataMove[0].Name;

                    await handleMoveSwap(pokemonName, moveName, message, token, socket, actionId);
                }

                if (message.evolve) {
                    const confirmed = await confirmPromise(`${pokemonName} is evolving! Continue?`);
                    await confirmAction(actionId, token, socket);
                    if (confirmed) {
                        const data = {
                            type: 'evolve_mon',
                            pokemonId: message.pokemonId,
                            evoType: 'EXP',
                            token: token
                        };
                        socket.send(JSON.stringify(data));
                        addEXP(message.pokemonId, lastExpToAdd, token, socket);
                        pokemonData = await getData('pokemonId', message.pokemonId);
                        pokemonNewName = pokemonData[0].Name;
                        await showModalPromise(`${pokemonName} evolved into ${pokemonNewName}!`);
                    } else {
                        await showModalPromise(`Huh? ${pokemonName} stopped evolving!`);
                    }
                    pokemonNewName = '';
                }
                resolve();
            } catch (error) {
                console.error('Error handling message:', error);
                reject(error);
            }
        });
    }

    function showModalPromise(message) {
        return new Promise((resolve) => {
            showModal(message, resolve);
        });
    }

    function confirmPromise(message) {
        return new Promise((resolve) => {
            showConfirmModal(message, resolve);
        });
    }

    function optionPromise(message, options) {
        return new Promise((resolve) => {
            showOptionModal(message, options, resolve);
        });
    }

    function promptPromise(message) {
        return new Promise((resolve) => {
            showPromptModal(message, resolve);
        });
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
        const message = JSON.parse(event.data);
        console.log('Incoming message:');
        console.log(message);
        getUnfinishedAction().then(function (unfinishedData) {
            try {
                const data = JSON.parse(unfinishedData);
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
        getPartyPokemon();
        getBoxPokemon();
    });

    async function handleMoveSwap(pokemonName, moveName, message, token, socket, actionId) {
        let moveConfirmed = false;
    
        while (!moveConfirmed) {
            const confirmed = await confirmPromise(
                `${pokemonName} is trying to learn ${moveName}. But, ${pokemonName} can't learn more than four moves! Delete an older move to make room for ${moveName}?`
            );
    
            if (confirmed) {
    
                const movesData = await getData('moves', message.pokemonId);
                console.log(movesData);
    
                // Extract move names from movesData
                const moveNames = movesData.map((move, index) => `${move.Name}`);
    
                const moveOrder = await optionPromise('Which move should be replaced?', moveNames);
                if (moveOrder !== null) {
                    const moveIndex = moveOrder.split(':')[0].split(' ')[1] - 1; // Extract index from the selected option
                    const data = {
                        type: 'learn_move',
                        pokemonId: message.pokemonId,
                        moveId: message.moveSwap,
                        moveOrder: moveIndex,
                        token: token
                    };
                    socket.send(JSON.stringify(data));
                    await showModalPromise(`${pokemonName} learned ${moveName}!`);
                    moveConfirmed = true;
                } else {
                    console.log("Move learning was cancelled by user.");
                }
            } else {
                await showModalPromise(`${pokemonName} didn't learn ${moveName}!`);
                moveConfirmed = true;
            }
        }
    
        await confirmAction(actionId, token, socket);
    }
    $('#addExp').on('click', async function () {
        const pokemonId = $('#addexp-pokemonId').val();
        var exp = $('#addexp-exp').val();
        addEXP(pokemonId, exp, token, socket);
        $('.move-info').hide();
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

    $('#battle').on('click', async function () {

        const pokemonId = $('#addexp-pokemonId').val();

        var exp = $('#addexp-exp').val();

        addEXP(pokemonId, exp, token, socket);
    });
});
