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
            showLoadingCircle('Loading data...');
            socket.addEventListener('message', function onConfirm(event) {
                const message = JSON.parse(event.data);
                if (message.responseFrom === 'confirm_action' && message.actionId === actionId) {
                    hideLoadingCircle();
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
            showLoadingCircle('Loading action...')
            $.ajax({
                url: 'getUnfinishedAction.php',
                type: 'GET',
                success: function (response) {
                    message = JSON.parse(response);
                    console.log(message);
                    resolve(response);
                    hideLoadingCircle();
                    eraseCookie('ExpPokemonId');
                },
                error: function (xhr, status, error) {
                    console.error(error);
                    reject(error);
                    hideLoadingCircle();
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

            await fetchUnfinishedTasks();

            const nonEvolveTasks = unfinishedTasks.filter(task => !task.evolve);
            const evolveTasks = unfinishedTasks.filter(task => task.evolve);

            const groupedNonEvolveTasks = nonEvolveTasks.reduce((acc, task) => {
                if (task && task.pokemonId) {
                    if (!acc[task.pokemonId]) {
                        acc[task.pokemonId] = [];
                    }
                    acc[task.pokemonId].push(task);
                }
                return acc;
            }, {});

            const lastProcessedPokemonId = getCookie('ExpPokemonId');
            let orderedPokemonIds = Object.keys(groupedNonEvolveTasks);
            if (lastProcessedPokemonId) {
                orderedPokemonIds = [lastProcessedPokemonId, ...orderedPokemonIds.filter(id => id !== lastProcessedPokemonId)];
            }

            const orderedNonEvolveTasks = orderedPokemonIds.flatMap(id => groupedNonEvolveTasks[id]);
            unfinishedTasks = [...orderedNonEvolveTasks, ...evolveTasks];

            while (unfinishedTasks.length > 0) {
                const task = unfinishedTasks.shift();
                if (!task) {
                    console.warn('Skipping undefined task');
                    console.log(task);
                    continue;
                }
                try {
                    await handleMessage(task, token, socket);
                } catch (error) {
                    console.error('Error processing task:', error);
                    showModalPromise('Error!');
                }
                location.reload();
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

    async function displayAlertModal(data) {
        hideLoadingCircle();
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

    async function displayConfirmModal(data) {
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


    async function displayOptionModal(data) {

        const $modal = $('#option-modal');
        const $modalMessage = $('#option-message');
        const $optionList = $('#option-list');
        const $okButton = $('#option-confirm-button');
        const $cancelButton = $('#option-cancel-button');
        let selectedOption = null;

        $modalMessage.html(data.message);
        $optionList.empty();

        data.options.forEach(option => {
            const $optionButton = $('<button class="option-button"></button>').html(option).click(function () {
                $('.option-button').removeClass('selected');
                $(this).addClass('selected');
                selectedOption = option;
            });
            $optionList.append($optionButton);
        });

        $modal.show();

        $okButton.off('click').on('click', function () {
            if (selectedOption === null) {
                alert('Please select a move before confirming.');
            } else {
                $modal.hide();
                if (typeof data.callback === 'function') {
                    data.callback(selectedOption);
                }
                isModalShowing = false;
                displayNextModal();
            }
        });

        $cancelButton.off('click').on('click', async function () {
            $modal.hide();
            if (typeof data.callback === 'function') {
                data.callback(null);
            }
            isModalShowing = false;
            displayNextModal();
        });
    }

    async function displayPromptModal(data) {

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
                    await showModalPromise(`${pokemonName} grew to level ${message.levelup}!`);
                    await confirmAction(actionId, token, socket);
                    await setCookie('ExpPokemonId', message.pokemonId);
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

                    await setCookie('ExpPokemonId', message.pokemonId);
                    await confirmAction(actionId, token, socket);
                }

                if (message.moveSwap && (Array.isArray(message.moveSwap) ? message.moveSwap.length > 0 : message.moveSwap !== 0)) {
                    const moveId = message.moveSwap;
                    const dataMove = await getData('moveId', moveId);

                    await setCookie('ExpPokemonId', message.pokemonId);
                    await handleMoveSwap(pokemonName, dataMove, message, token, socket, actionId);
                }

                if (message.evolve) {
                    const confirmed = await confirmPromise(`${pokemonName} is evolving! Continue?`);

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
                    await confirmAction(actionId, token, socket);
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
        showLoadingCircle('Loading...');
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
        showModalPromise('Error!');
        console.error('WebSocket error:', event);
    });

    socket.addEventListener('message', async function (event) {
        showLoadingCircle('Loading...');
        getPartyPokemon(function(response) {
            var container = $('#pokemon-container');
            displayPokemonParty(response, container);
        });
        getBoxPokemon();
        const message = JSON.parse(event.data);
        console.log('Incoming message:');
        console.log(message);
        if (message.responseFrom != 'battle') {
            getUnfinishedAction().then(function (unfinishedData) {
                hideLoadingCircle();
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
        }
        if (message.responseFrom === 'battle') {
            showLoadingCircle('Loading battle...');
        
            const data = message.data;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'battle.php';
        
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'data';
            input.value = data;
        
            form.appendChild(input);
        
            document.body.appendChild(form);
            form.submit();
        }
        
    });

    async function handleMoveSwap(pokemonName, moveData, message, token, socket, actionId) {
        let moveConfirmed = false;
        const moveName = moveData[0].Name;

        $(document).on('mouseenter', '.old-move', function () {
            $(this).find('.move-info-popup').stop(true, true).slideDown(200);
        }).on('mouseleave', '.old-move', function () {
            setTimeout(() => {
                $(this).find('.move-info-popup').stop(true, true).slideUp(200);
            }, 100);
        });

        $(document).on('mouseenter', '.option-button', function () {
            $(this).find('.move-info-popup').stop(true, true).slideDown(200);
        }).on('mouseleave', '.option-button', function () {
            setTimeout(() => {
                $(this).find('.move-info-popup').stop(true, true).slideUp(200);
            }, 100);
        });

        while (!moveConfirmed) {
            const confirmed = await confirmPromise(
                `${pokemonName} is trying to learn ${moveName}. But, ${pokemonName} can't learn more than four moves! Delete an older move to make room for ${moveName}?`
            );

            if (confirmed) {
                const movesData = await getData('moves', message.pokemonId);
                const moveNames = movesData.map((move) => {
                    return `${move.Name}
                        <div class="move-info-popup" style="display:none;">
                            ${generateMoveInfoHtml(move).html()}
                        </div>`;
                });

                var moveOrder = await optionPromise(
                    `Which move should be replaced?<br/>
                    New move: 
                    <div class="old-move">${moveName}<br/>
                    <div class="move-info-popup" style="display:none;">
                        ${generateMoveInfoHtml(moveData[0]).html()}
                    </div></div>
                    Select one of the moves below:`,
                    moveNames
                );

                moveOrder = moveOrder.split('<')[0].trim();

                if (moveOrder !== null) {
                    const oldMove = movesData.find(move => move.Name === moveOrder);
                    if (oldMove) {
                        const data = {
                            type: 'learn_move',
                            pokemonId: message.pokemonId,
                            moveId: message.moveSwap,
                            moveOrder: oldMove.MoveOrder,
                            token: token
                        };
                        socket.send(JSON.stringify(data));
                        await showModalPromise(`${pokemonName} learned ${moveName}!`);
                        moveConfirmed = true;
                    } else {
                        console.error("Selected move not found in moves data.");
                    }
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
        showLoadingCircle('Loading data...');

        const pokemonIds = $('#addexp-pokemonId').val().split(',').map(id => id.trim()).filter(id => id);
        const expValues = $('#addexp-exp').val().split(',').map(exp => exp.trim()).filter(exp => !isNaN(exp) && exp !== '').map(Number);

        if (pokemonIds.length !== expValues.length) {
            hideLoadingCircle();
            alert('The number of Pokémon IDs and experience values must match.');
            return;
        }

        try {
            for (let i = 0; i < pokemonIds.length; i++) {
                const pokemonId = pokemonIds[i];
                const exp = expValues[i];
                await addEXP(pokemonId, exp, token, socket);
            }
        } catch (error) {
            console.error('Error adding experience:', error);
            alert('Failed to add experience.');
        }

        hideLoadingCircle();
        $('.move-info').hide();
    });



    $('#addPokemon').click(async function () {
        showLoadingCircle('Loading data...');
        const pokedexId = $('#addPokemon-PokedexId').val();
        const level = $('#addPokemon-level').val();
        const nick = $('#addPokemon-Nick').val();

        if (pokedexId && level) {
            const data = {
                type: 'add_mon',
                pokedexId: pokedexId,
                level: level,
                nick: nick,
                token: token
            };
            socket.send(JSON.stringify(data));
            getPartyPokemon(function(response) {
                var container = $('#pokemon-container');
                displayPokemonParty(response, container);
            });
            getBoxPokemon();
        } else {
            alert('Please enter pokedex ID and level');
        }
        hideLoadingCircle();
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
            getPartyPokemon(function(response) {
                var container = $('#pokemon-container');
                displayPokemonParty(response, container);
            });
            getBoxPokemon();
        }
    });

    $('#battle').on('click', async function () {
        const enemyUserId = $('#battle-enemyid').val();

        if (enemyUserId) {
            const data = {
                type: 'battle',
                enemyUserId: enemyUserId,
                token: token
            };
            socket.send(JSON.stringify(data));
            getPartyPokemon(function(response) {
                var container = $('#pokemon-container');
                displayPokemonParty(response, container);
            });
            getBoxPokemon();
        } else {
            alert('Please enter pokedex ID and level');
        }
        hideLoadingCircle();
    });
});
