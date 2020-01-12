
var [init, Chat, Controller, Room, Player] = (function($, socket){

    var Chat = function(socket) {
        this.tab = 'global';
        this.socket = socket;

        var self = this;
        /**
         * Event processors
         */
        this.global_event = function(event, data, extra) {
            var chat_window = $('.chat-window[data-nav="global"]'),
                msg = '';

            switch (event) {
                case 'connect':
                    msg = '<strong>' + data + '</strong> se ha conectado.';
                    break;
                case 'disconnect':
                    msg = '<strong>' + data + '</strong> se ha desconectado.';
                    break;
                case 'message':
                    msg = '<strong>' + data + '</strong>: ' + extra;
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
                    msg = '<strong>' + data + '</strong> ha entrado en la sala.';
                    break;
                case 'leave':
                    msg = '<strong>' + data + '</strong> ha salido de la sala.';
                    break;
                case 'message':
                    msg = '<strong>' + data + '</strong>: ' + extra;
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
            self.tab = $(this).data('nav');

            $('.chat-tab').removeClass('active');
            $(this).addClass('active');

            $('.chat-window').addClass('d-none');
            $('.chat-window[data-nav="' + self.tab + '"]').removeClass('d-none');
        };

        this.sendMessage = function(e) {
            var msg = $('#chat-message').val().trim();
            $('#chat-message').val('');

            if (msg.length == 0) return;

            self.socket.emit('message', {
                chat: self.tab,
                msg: msg
            });
        };

        this.keyPress = function(e) {
            if (!e) e = window.event;
            var keyCode = e.keyCode || e.which;
            if (keyCode == '13') {
                e.preventDefault();
                e.stopPropagation();
                self.sendMessage();
                return false;
            }
        };

    }

    var Controller = function(ws, id, token, callback) {
        this.id = null;
        this.rooms = {};
        this.players = {};
        this.chat = new Chat(socket);

        var self = this;
        /**
         * Static creators
         */
        this.createPlayer = function(player) {
            return new Player(player.id, player.username);
        };
        this.createRoom = function(room) {
            var r = new Room(room.id, room.status, room.numplayers);

            $.each(room.players, function(i, player) {
                r.add_player(controller.get_player(player));
            });
            $.each(room.spectators, function(i, player) {
                r.add_spectators(controller.get_player(player));
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
            self.add_player(self.createPlayer(player));
            self.chat.global_event('connect', self.get_player(player.id).username);
            // render_player(data.id);
        };

        this.playerDisconnect = function(id) {
            self.chat.global_event('disconnect', self.get_player(id).username);
            self.remove_player(id);
            // unrender_player(data.id);
        };

        this.playerMessage = function(chat, username, msg) {
            switch(data.chat){
                case 'room':
                    self.chat.local_event('message', self.get_player(data.playerid).username, data.msg);
                    break;
                case 'global':
                    self.chat.global_event('message', self.get_player(data.playerid).username, data.msg);
                    break;
                default:
                    console.error("Unknown chat", data);
            }
        };

        this.playerJoinRoom = function(data){
            var player = self.get_player(data.playerid),
                room = self.get_room(data.roomid);
            
            player.set_room(room);
            room.add_player(player);

            player.render();
            room.render();
        };

        this.playerSpectateRoom = function(data){
            var player = self.get_player(data.playerid),
                room = self.get_room(data.roomid);

            player.set_room(room);
            room.add_spectator(player);

            player.render();
            room.render();
        };

        this.playerLeaveRoom = function(data){
            var player = self.get_player(data.playerid),
                room = self.get_room(data.roomid);
            
            player.unset_room();
            room.remove_player(data.playerid);
            room.remove_spectator(data.playerid);

            player.render();
            room.render();
        };

        this.ready = function(time) {
            self.readyModal = new gModal({
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
                            self.pendingModal = new gModal({
                                title: 'La partida va a empezar',
                                body: '<center>Esperando al resto de jugadores...</center>',
                                close: { closable: false }
                            });
                            self.pendingModal.show();
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
            self.readyModal.show();
        };

        this.unready = function() {
            if (typeof self.readyModal != 'undefined') self.readyModal.hide();
            if (typeof self.pendingModal != 'undefined') self.pendingModal.hide();
        };

        this.play = function() {
            if (typeof self.readyModal != 'undefined') self.readyModal.hide();
            if (typeof self.pendingModal != 'undefined') self.pendingModal.hide();

            $('#loading').hide();
            $('#rooms').hide();
            $('#play').show();

            socket.emit('action', { action: 'ack', event: 'play' });
        };

        /**
         * Client actions
         */
        this.joinRoom = function() {
            var targetRoom = self.get_room($(this).data('room'));
            var currentRoom = self.room();

            if (currentRoom !== null) {
                self.leaveRoom();
            }

            socket.emit('join', { room: targetRoom.id });
        };

        this.leaveRoom = function() {
            socket.emit('leave', { room: self.room().id });
        };

        this.spectateRoom = function() {
            var targetRoom = self.get_room($(this).data('room'));
            var currentRoom = self.room();

            if (currentRoom !== null) {
                self.leaveRoom();
            }

            socket.emit('spectate', { room: self.room().id });
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

        /**
         * Init. DOM Binding
         */
        this.setup = function(ws, id, token, callback){
            self.connected = false;
            self.socket = io(ws);
            
            self.socket.on('connect', function() {
                if(self.connected){
                    window.location.reload();
                    return false;
                }
                
                // Success authentication
                self.socket.on('successAuth', function(data) {
                    self.connected = true;
                    self.id = data.id;

                    $.each(data.players, function(pid, player) {
                        self.add_player(controller.createPlayer(player));
                    });
            
                    $.each(data.rooms, function(rid, room) {
                        controller.add_room(controller.createRoom(room));
                    });
            
                    controller.render();

                    // Chat
                    $('#chat-submit').on('click',       self.chat.sendMessage);
                    $('#chat-message').on('keypress',   self.chat.keyPress);
                    $('.chat-tab').on('click',          self.chat.switchTab);
                    self.chat.triggerSwitchTab('global');

                    // Lobby
                    socket.on('playerConnect',      self.playerConnect);
                    socket.on('playerDisconnect',   self.playerDisconnect);
                    socket.on('playerMessage',      self.playerMessage);
                    socket.on('playerJoinRoom',     self.playerJoinRoom);
                    socket.on('playerSpectateRoom', self.playerSpectateRoom);
                    socket.on('playerLeaveRoom',    self.playerLeaveRoom);

                    
                    $(document).on('click', '.join',        self.joinRoom);
                    $(document).on('click', '.spectate',    self.joinRoom);
                    $(document).on('click', '.leave',       self.leaveRoom);

                    socket.on('ready',      self.ready);
                    socket.on('unready',    self.unready);

                    callback();
                });
    
                // @TODO: Auth with id and token.
                self.socket.emit('auth', {            
                    username: 'User' + Math.floor(Math.random() * 54623523)
                });    
            });

            self.socket.on('disconnected', function() {
                self.socket.emit('disconnect');
            });
        }
        
        this.setup(ws, id, token, callback);
    }

    var Room = function(id) {
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
                    "<button class='join' data-room='" +
                    this.id +
                    "'>Entrar</button>" +
                    players_html +
                    '</div>'
            );
        };
    }

    var Player = function(id, username) {
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
        this.unset_room = function() {
            this.room = null;
        };

        /**
         * UI
         */
        this.render = function() {
            // console.error('Player:render not implemented');
        };
    }

    return [init, Chat, Controller, Room, Player];
})(jQuery, socket)