(function($, port) {
    function Chat(controller) {
        this.tab = 'global';
        this.controller = controller;

        /**
         * Event processors
         */
        this.global_event = function(event, data, extra) {
            var chat_window = $('.chat-window[data-nav="global"]'),
                msg = '';

            switch (event) {
                case 'connect':
                    msg = '<strong>' + data.username + '</strong> se ha conectado.';
                    break;
                case 'disconnect':
                    msg = '<strong>' + data.username + '</strong> se ha desconectado.';
                    break;
                case 'message':
                    msg = '<strong>' + data.username + '</strong>: '.extra;
                    break;
            }

            if (msg != '') {
                chat_window.append('<p>' + msg + '</p>');
                chat_window[0].scrollTo(0, $(chat_window).height());
            }
        };

        this.local_event = function(event, data, extra) {
            var chat_window = $('.chat-window[data-nav="room"]'),
                msg = '';

            switch (event) {
                case 'join':
                    msg = '<strong>' + data.username + '</strong> ha entrado en la sala.';
                    break;
                case 'leave':
                    msg = '<strong>' + data.username + '</strong> ha salido de la sala.';
                    break;
                case 'message':
                    msg = '<strong>' + data.username + '</strong>: '.extra;
                    break;
            }

            if (msg != '') {
                chat_window.append('<p>' + msg + '</p>');
                chat_window[0].scrollTo(0, $(chat_window).height());
            }
        };

        /**
         * UI Handlers
         */
        this.triggerSwitchTab = function(tab) {
            $('.chat-tab[data-nav="' + tab + '"').trigger('click');
        };

        this.switchTab = function(e) {
            this.tab = $(this).data('nav');

            $('.chat-tab').removeClass('active');
            $(this).addClass('active');

            $('.chat-window').addClass('d-none');
            $('.chat-window[data-nav="' + this.tab + '"]').removeClass('d-none');
        };

        this.sendMessage = function(e) {
            var msg = $('#chat-message').val().trim();
            $('#chat-message').val('');

            if (msg.length == 0) return;

            socket.emit('message', {
                chat: this.tab,
                msg: msg
            });
        };

        this.keyPress = function(e) {
            if (!e) e = window.event;
            var keyCode = e.keyCode || e.which;
            if (keyCode == '13') {
                e.preventDefault();
                e.stopPropagation();
                this.sendMessage();
                return false;
            }
        };

        /**
         * Handlers
         */
        this.playerMessage = function(data) {
            switch (data.chat) {
                case 'global':
                    this.global_event(
                        'message',
                        this.controller.get_player(data.playerid),
                        data.msg
                    );
                    break;
                case 'room':
                default:
                    this.local_event(
                        'message',
                        this.controller.get_player(data.playerid),
                        data.msg
                    );
                    break;
            }
        };
    }

    function Controller(id) {
        this.id = id;

        this.rooms = {};
        this.players = {};

        this.chat = new Chat(this);

        /**
         * Static creators
         */
        this.createPlayer = function(player) {
            return new Player(player.id, player.username);
        };

        this.createRoom = function(room) {
            var r = new Room(room.id, room.status, room.numplayers);

            $.each(room.players, function() {
                r.add_player(controller.get_player(this));
            });
            $.each(room.spectators, function() {
                r.add_spectators(controller.get_player(this));
            });
            return r;
        };

        /**
         * Current player
         */
        this.player = function() {
            return this.get_player(this.id);
        };

        this.room = function() {
            return this.get_player(this.id).get_room();
        };

        /**
         * Players
         */
        this.add_player = function(player) {
            this.players[player.id] = player;
        };

        this.get_player = function(id) {
            return this.players[id];
        };

        this.remove_player = function(id) {
            delete this.players[id];
        };

        /**
         * Rooms
         */
        this.add_room = function(room) {
            this.rooms[room.id] = room;
        };

        this.get_room = function(id) {
            return this.rooms[id];
        };

        this.remove_room = function(id) {
            delete this.rooms[id];
        };

        /**
         * Server events
         */
        this.playerConnect = function(player) {
            this.add_player(this.createPlayer(player));
            this.chat.global_event('connect', this.get_player(player.id));
            // render_player(data.id);
        };

        this.playerDisconnect = function(id) {
            this.chat.global_event('disconnect', this.get_player(id));
            this.remove_player(id);
            // unrender_player(data.id);
        };

        this.updatePlayer = function(player) {
            this.get_player(player.id).update(player);
        };

        this.updateRoom = function(room) {
            this.get_room(room.id).update(room);
        };

        this.ready = function(time) {
            this.readyModal = new gModal({
                title: 'La partida va a empezar. ¿Estás list@?',
                body: '<div class="ready-timer">' + time + '</div>',
                buttons: [
                    {
                        content: '¡Vamos!',
                        classes: 'gmodal-button-blue',
                        bindKey: 13 /* Enter */,
                        callback: function(modal) {
                            socket.emit('ready');
                            modal.hide();
                            this.pendingModal = new gModal({
                                title: 'La partida va a empezar',
                                body: '<center>Esperando al resto de jugadores...</center>',
                                close: { closable: false }
                            });
                            this.pendingModal.show();
                        }
                    }
                ],
                close: {
                    closable: true,
                    location: 'in' /* 'in' or 'out' (side) the modal */,
                    bindKey: 27 /* Esc */,
                    callback: function(modal) {
                        socket.emit('unready');
                        modal.hide();
                    }
                },
                onShow: function(modal) {
                    var interval = setInterval(function() {
                        --time;
                        $('.ready-timer').text(time);

                        if (time <= 0) clearInterval(interval);
                    }, 1000);

                    // @TODO: Remove
                    // setTimeout(function(){
                    // $("#gmodal-wrapper-" + readyModal.id + " .gmodal-button.gmodal-button-blue").trigger("click");
                    // }, 500);
                }
            });
            this.readyModal.show();
        };

        this.unready = function() {
            if (typeof this.readyModal != 'undefined') this.readyModal.hide();
            if (typeof this.pendingModal != 'undefined') this.pendingModal.hide();
        };

        this.play = function() {
            if (typeof this.readyModal != 'undefined') this.readyModal.hide();
            if (typeof this.pendingModal != 'undefined') this.pendingModal.hide();

            $('#loading').hide();
            $('#rooms').hide();
            $('#play').show();

            socket.emit('action', { action: 'ack', event: 'play' });
        };

        /**
         * Client actions
         */
        this.joinRoom = function() {
            var targetRoom = this.get_room($(this).data('room'));
            var currentRoom = this.room();

            if (currentRoom !== null) {
                this.leaveRoom();
            }

            socket.emit('join', { room: targetRoom.id });
        };

        this.leaveRoom = function() {
            socket.emit('leave', { room: this.room().id });
        };

        this.spectateRoom = function() {
            var targetRoom = this.get_room($(this).data('room'));
            var currentRoom = this.room();

            if (currentRoom !== null) {
                this.leaveRoom();
            }

            socket.emit('spectate', { room: this.room().id });
        };

        this.render = function() {
            $.each(this.rooms, function(id, room) {
                room.render();
            });
            $.each(this.players, function(id, player) {
                player.render();
            });

            $('#loading').hide();
            $('#play').hide();
            $('#rooms').css('display', 'flex');
        };
    }

    function Room(id) {
        this.id = id;
        this.players = {};
        this.spectators = {};

        /**
         * Equals
         */
        this.equals = function(room) {
            if (typeof room.id !== 'undefined') return false;
            return this.id === room.id;
        };

        /**
         * Players
         */
        this.add_player = function(player) {
            this.players[player.id] = player;
        };
        this.remove_player = function(id) {
            delete this.players[id];
        };

        /**
         * Spectators
         */
        this.add_spectator = function(player) {
            this.spectators[player.id] = player;
        };
        this.remove_spectator = function(id) {
            delete this.spectators[id];
        };

        /**
         * Update
         */
        this.update = function(data) {
            console.error('Room:update not implemented', data);
        };

        /**
         * UI
         */
        this.render = function() {
            if ($('#room_' + this.id).length == 0) {
                $('#rooms').append(
                    "<div id='room_" +
                        this.id +
                        "' class='room col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12'>" +
                        '</div>'
                );
            }
    
            var players_html = '';
            $.each(this.players, function(id, player) {
                players_html += '<p>' + player.username + '</p>';
            });
    
            $('#room_' + this.id).html(
                "<div class='room-header'>Mesa " +
                    this.id +
                    '</div>' +
                    "<div class='room-content'>" +
                    "<button class='join_room' data-room='" +
                    this.id +
                    "'>Entrar</button>" +
                    players_html +
                    '</div>'
            );
        };
    }

    function Player(id, username) {
        this.id = id;
        this.username = username;
        this.room = null;

        /**
         * Room
         */
        this.set_room = function(room) {
            this.room = room;
        };
        this.get_room = function() {
            return this.room;
        };

        /**
         * Update
         */
        this.update = function(data) {
            console.error('Player:update not implemented', data);
        };

        /**
         * UI
         */
        this.render = function() {
            console.error('Player:render not implemented');
        };
    }

    /**
     * GAME 
     * SPECITIC
     */

    Controller.prototype.set_game = function(game) {
        this.game = game;
    };
    Controller.prototype.unset_game = function() {
        this.game = undefined;
    };

    Controller.prototype.request_dices = function(data) {
        if (typeof this.game !== undefined) this.game.request_dices(data);
    };
    Controller.prototype.throw_dices = function() {
        if (typeof this.game !== undefined) this.game.throw_dices();
    };
    Controller.prototype.info_dices = function(data) {
        if (typeof this.game !== undefined) this.game.info_dices(data);
    };

    Controller.prototype.request_move = function(data) {
        if (typeof this.game !== undefined) this.game.request_move(data);
    };
    Controller.prototype.start_move = function(data) {
        if (typeof this.game !== undefined) this.game.start_move(data);
    };
    Controller.prototype.info_move = function(data) {
        if (typeof this.game !== undefined) this.game.info_move(data);
    };
    Controller.prototype.info_die = function(data) {
        if (typeof this.game !== undefined) this.game.info_die(data);
    };

    Controller.prototype.skip_move = function(data) {
        if (typeof this.game !== undefined) this.game.skip_move(data);
    };
    Controller.prototype.skip_double = function(data) {
        if (typeof this.game !== undefined) this.game.skip_double(data);
    };

    /**
     * Player extension
     */
    Player.prototype.set_color = function(color) {
        this.color = color;
    };

    Player.prototype.get_color = function() {
        return this.color;
    };

    Player.prototype.init_chips = function() {
        this.chips = {};
    };

    Player.prototype.add_chip = function(chip) {
        this.chips[chip.id] = chip;
    };

    Player.prototype.get_chip = function(id) {
        return this.chips[id];
    };

    Player.prototype.get_chips = function() {
        return Object.values(this.chips);
    };

    Player.prototype.highlight = function(status) {
        this.color.highlight(status);
    };

    Player.prototype.highlight_chips = function(status, moves) {
        $.each(this.chips, function(id, chip) {
            chip.highlight(false);
        });
        if (status) {
            for (var i = 0; i < moves.length; i++) {
                this.get_chip(moves[i][0]).highlight(true);
            }
        }
    };

    Player.prototype.display = function() {
        this.color.element.find('.username').text(this.username);
        this.color.element.find('.user').show();
    };

    function Color(id, name, initial, breaker, postbreak, finish, domElement) {
        this.id = id;
        this.name = name;

        this.initial = initial;
        this.breaker = breaker;
        this.postbreak = postbreak;
        this.finish = finish;

        this.element = domElement;

        this.get_position = function() {
            var top = parseInt(this.element.css('top')),
                left = parseInt(this.element.css('left')),
                size = 30,
                margin = size / 2.5;

            return {
                top: top == 0 ? size - margin : 100 - size + margin,
                left: left == 0 ? size - margin : 100 - size + margin
            };
        };

        this.get_finish_position = function(index) {
            var pos = {
                top:
                    $('#area_center').offset().top -
                    $('#play').offset().top +
                    $('#area_center').height() / 2 -
                    $('.chip').width() / 2,
                left:
                    $('#area_center').offset().left -
                    $('#play').offset().left +
                    $('#area_center').width() / 2 -
                    $('.chip').height() / 2
            };

            var margin = 0;
            switch (index) {
                case 0:
                    margin -= $('.chip').width() * 1.7;
                    break;
                case 1:
                    margin -= $('.chip').width() * 0.6;
                    break;
                case 2:
                    margin += $('.chip').width() * 0.6;
                    break;
                case 3:
                    margin += $('.chip').width() * 1.7;
                    break;
            }

            switch (this.id) {
                case 0:
                    pos.top -= $('.dices').height() / 2 + $('.chip').height() * 1.05;
                    pos.left += margin;
                    break;
                case 1:
                    pos.left -= $('.dices').width() / 2 + $('.chip').width() * 1.05;
                    pos.top += margin;
                    break;
                case 2:
                    pos.top += $('.dices').height() / 2 + $('.chip').height() * 1.05;
                    pos.left += margin;
                    break;
                case 3:
                    pos.left += $('.dices').width() / 2 + $('.chip').width() * 1.05;
                    pos.top += margin;
                    break;
            }
            return pos;
        };

        this.get_next = function(position) {
            if (position == -1) return this.initial;
            if (position === this.breaker) return this.postbreak;
            if (position == this.finish) return false;
            if (position == 68) return 1;
            return position + 1;
        };

        this.highlight = function(status) {
            if (status) this.element.addClass('active');
            else this.element.removeClass('active');
        };
    }

    function Chip(id, position, color, game, domElement) {
        this.id = id;
        this.position = position;
        this.color = color;
        this.game = game;
        this.element = domElement;

        this.go_home = function(time, callback) {
            var home = this.color.get_position(),
                size = 3;

            switch (this.id) {
                case 0:
                    home.top -= size;
                    home.left -= size;
                    break;
                case 1:
                    home.top -= size;
                    home.left += size;
                    break;
                case 2:
                    home.top += size;
                    home.left -= size;
                    break;
                case 3:
                    home.top += size;
                    home.left += size;
                    break;
                default:
                    console.log("Error: id: '" + this.id + "' go_home");
            }

            if (typeof time === 'undefined' || time == 0) {
                this.element.css({
                    top: home.top + '%',
                    left: home.left + '%'
                });
            } else {
                this.element.animate(
                    {
                        top: home.top + '%',
                        left: home.left + '%'
                    },
                    time,
                    callback
                );
            }
        };

        this.go_to = function(to, side, time, callback) {
            var css;
            if (to == this.color.finish) {
                css = this.color.get_finish_position(this.id);
            } else {
                css = this.game.get_box_position(to, side);
            }

            if (typeof time === 'undefined' || time == 0) {
                this.element.css(css);
            } else {
                this.element.animate(css, time, callback).delay(10);
            }
            this.position = to;
        };

        this.move = function(to, side, callback) {
            if (to == -1) {
                this.position = -1;
                this.go_home(500, callback);
                return;
            }

            var from = this.position;
            while (true) {
                this.position = this.color.get_next(this.position);
                if (this.position == to) {
                    this.go_to(this.position, side, 75);
                    break;
                } else {
                    this.go_to(this.position, false, 75, callback);
                }
            }
        };

        this.highlight = function(status) {
            if (status) {
                this.element.addClass('active');
            } else {
                this.element.removeClass('active');
            }
        };

        this.render = function() {
            this.move(this.position);
            this.element.css('display', 'block');
        };
    }

    function Game(id) {
        this.id = id;

        this.players = {};
        this.turn = null;
        this.moves = [];
        this.dices = [];

        /**
         * Players
         */
        this.add_player = function(player) {
            this.players[player.id] = player;
        };

        this.get_player = function(id) {
            return this.players[id];
        };

        /**
         * Turn
         */
        this.my_turn = function() {
            return this.id === this.turn;
        };

        this.highlight_turn = function() {
            $.each(this.players, function(i, player) {
                player.highlight(this.my_turn());
            });
        };

        this.skip_move = function(playerid) {
            console.log(playerid, 'No puedo mover!');
        };

        this.skip_double = function(playerid) {
            console.log(playerid, 'Pierdo el turno!');
        };

        /**
         * Dices
         */
        this.request_dices = function(playerid) {
            this.turn = playerid;
            this.dices = [];
            if (this.my_turn()) {
                $('.dices').addClass('active');
            }
            this.highlight_turn();
        };

        this.throw_dices = function() {
            if (this.my_turn() && this.dices.length == 0) {
                audio.dices.play();
                socket.emit('action', { action: 'dices' });
                $('.dices').removeClass('active');
            }
        };

        this.info_dices = function(dices) {
            if (!this.my_turn()) {
                audio.dices.play();
            }
            this.dices = dices;
            $('#dice1').removeClass('used');
            $('#dice2').removeClass('used');
            $('#dice1').css('background-image', "url('/assets/parchis/dice" + dices[0] + ".svg')");
            $('#dice2').css('background-image', "url('/assets/parchis/dice" + dices[1] + ".svg')");
        };

        /**
         * Moves
         */
        this.dragchip = false;

        this.request_move = function(data) {
            // @TODO: FIX: Consider "dices" x10 and x20.
            switch (data.dices.length) {
                case 2:
                    $('#dice1').removeClass('used');
                    $('#dice2').removeClass('used');
                    break;
                case 1:
                    if (data.dices[0] == this.dices[0]) {
                        $('#dice2').addClass('used');
                    } else {
                        $('#dice1').addClass('used');
                    }
                case 0:
                    $('#dice1').addClass('used');
                    $('#dice2').addClass('used');
                    break;
            }

            this.turn = data.id;
            if (this.my_turn()) {
                this.moves = data.moves;
                this.players[id].highlight_chips(true, this.moves);
            }
        };

        this.highlight_moves = function(status, chip) {
            $('.box').removeClass('active');

            if (this.turn == id && status) {
                var [type, color, cid] = chip.attr('id').split('_');

                if (color != this.players[id].color.name) return;

                for (var i = 0; i < this.moves.length; i++) {
                    if (this.moves[i][0] == cid) {
                        $('#box_' + this.moves[i][1]).addClass('active');
                    }
                }
            }
        };

        this.start_move = function(e) {
            var chip = $(this);
            if (this.my_turn()) {
                this.dragchip = false;
            } else {
                var [type, color, cid] = chip.attr('id').split('_');
                if (this.get_player(this.id).color.name === color) {
                    this.dragchip = cid;
                } else {
                    this.dragchip = false;
                }
            }
        };

        this.make_move = function(e) {
            e.preventDefault();
            var box = $(this);
            if (!this.my_turn()) {
                return false;
            }
            if (!this.dragchip) {
                return false;
            }

            var to = box.attr('id').split('_')[1];

            for (var i = 0; i < this.moves.length; i++) {
                if (this.moves[i][0] == this.dragchip && this.moves[i][1] == to) {
                    socket.emit('action', { action: 'move', id: this.dragchip, to: to });
                    return;
                }
            }
        };

        this.confirm_move = function(data) {
            var player = this.get_player(data.id);

            if (data.id == this.id) {
                player.highlight_chips(false);
                player.highlight(false);
            }

            var chip = player.get_chip(data.chip),
                from = chip.position,
                side = false;

            $.each(this.players, function(pid, player) {
                $.each(player.get_chips(), function(cid, chip) {
                    if (chip.position == data.to && data.to != -1) {
                        chip.go_to(chip.position, 'left');
                        side = 'right';
                    } else if (chip.position == from && from != -1) {
                        chip.go_to(from, false);
                    }
                });
            });
            player.get_chip(data.chip).move(data.to, side, function() {
                audio.chip.play();
            });
        };

        this.confirm_die = function(data) {
            this.get_player(data.id).get_chip(data.chip).move(-1);
        };

        this.get_box_position = function(bid, side) {
            var rotated = (bid >= 9 && bid <= 25) || (bid >= 43 && bid <= 59),
                box = $('#box_' + bid),
                pos = {
                    left:
                        box.offset().left -
                        $('#play').offset().left +
                        (rotated ? box.height() : box.width()) / 2 -
                        $('.chip').width() / 2,
                    top:
                        box.offset().top -
                        $('#play').offset().top +
                        (rotated ? box.width() : box.height()) / 2 -
                        $('.chip').height() / 2
                };

            switch (side) {
                case 'left':
                    if (rotated) pos.top -= $('.chip').width() * 0.65;
                    else pos.left -= $('.chip').width() * 0.65;
                    break;
                case 'right':
                    if (rotated) pos.top += $('.chip').width() * 0.65;
                    else pos.left += $('.chip').width() * 0.65;
                    break;
                default:
                    break;
            }

            return pos;
        };
    }

    /***
     * 
     * 
     * 
     * 
     * 
     */

    var socket = io('https://games.sowecms.com:' + port);
    var connected = false;
    var controller, game;
    var audio = {
        dices: new Audio('/assets/parchis/dices.mp3'),
        chip: new Audio('/assets/parchis/chip.mp3')
    };

    socket.on('connect', function() {
        if (connected) location.reload();
        connected = true;
    });

    socket.on('disconnected', function() {
        socket.emit('disconnect');
    });

    socket.emit('auth', {
        // @TODO: Authentication
        username: 'User' + Math.random()
    });

    socket.on('successAuth', function(data) {
        controller = new Controller(data.id);

        $.each(data.players, function(pid, player) {
            controller.add_player(controller.createPlayer(player));
        });

        $.each(data.rooms, function(rid, room) {
            controller.add_room(controller.createRoom(room));
        });

        controller.render();

        // Chat bindings
        $('#chat-submit').on('click', controller.chat.sendMessage);
        $('#chat-message').on('keypress', controller.chat.keyPress);
        $('.chat-tab').on('click', controller.chat.switchTab);
        controller.chat.triggerSwitchTab('global');

        // Remove bindings
        socket.on('playerConnect', controller.playerConnect);
        socket.on('playerDisconnect', controller.playerDisconnect);
        socket.on('playerMessage', controller.chat.message);
        socket.on('updatePlayer', controller.updatePlayer);
        socket.on('updateRoom', controller.updateRoom);

        $(document).on('click', '.join', controller.joinRoom);
        $(document).on('click', '.spectate', controller.joinRoom);
        $(document).on('click', '.leave', controller.leaveRoom);

        socket.on('ready', controller.ready);
        socket.on('unready', controller.unready);

        socket.on('play', function(data) {
            game = new Game(controller.id);
            $.each(data.players, function(i, player) {
                var color = new Color(
                    player.color.id,
                    player.color.name,
                    player.color.initial,
                    player.color.breaker,
                    player.color.postbreak,
                    player.color.finish,
                    $('#square_' + player.color.name)
                );

                controller.get_player(player.id).set_color(color);
                controller.get_player(player.id).init_chips();

                $.each(player.chips, function(j, chip) {
                    var chip = new Chip(
                        chip.id,
                        chip.position,
                        color,
                        game,
                        $('#chip_' + color.name + '_' + chip.id)
                    );
                    chip.render();
                    controller.get_player(player.id).add_chip(chip);
                });

                game.add_player(controller.get_player(player.id));
            });

            controller.set_game(game);
            controller.play();
        });

        $('.dices').on('click', controller.throw_dices);
        $('.chip').on('dragstart', controller.start_move);
        $('.box').on('drop', controller.make_move);

        socket.on('dices', controller.request_dices);
        socket.on('info_dices', controller.info_dices);
        socket.on('move', controller.request_move);
        socket.on('skip_move', controller.skip_move);
        socket.on('skip_double', controller.skip_double);
        socket.on('info_move', controller.info_move);
        socket.on('info_die', controller.info_die);

        $('.box').on('dragover', function(e) {
            e.preventDefault();
        });

        $('.chip').on('mouseover', function(e) {
            game.highlight_moves(true, $(this));
        });
        $('.chip').on('mouseout', function(e) {
            game.highlight_moves(false);
        });

        $(window).on('resize', function(e) {
            // Chat height
            $('.chat-window').css(
                'height',
                $(window).height() - $('.chat-nav').height() - $('.chat-input').height() - 20 + 'px'
            );

            // Play height
            var min = Math.min($('#game').height(), $('#game').width()) - 20;

            $('#play').css({ height: min, width: min });
        });
    });
})(jQuery, PORT);
