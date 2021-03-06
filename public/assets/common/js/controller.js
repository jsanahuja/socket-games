
var [Utils, Chat, Controller, Room, Player] = (function($){

    var Utils = {
        reconnect: function(){
            location.reload();
        },

        offline: function(){
            var offlineModal = new gModal({
                title: 'No hemos podido conectar con el servidor',
                body: '<p>El servidor está tardando demasiado en responder. Por favor, <strong>verifica que tienes conexión a internet</strong> e inténtalo de nuevo.</p>' +
                    '<ul><li>No tienes servicio de conexión a internet.</li>'+
                    '<li>Nuestros servicios estan en mantenimiento.</li></ul>'+
                    '<p>Por favor, verifica que tienes conexión a internet y vuelve a intentarlo</p>',
                buttons: [
                    {
                        content: 'Aceptar',
                        classes: 'btn btn-primary',
                        bindKey: 13 /* Enter */
                    }
                ],
                close: {
                    closable: true,
                    location: 'in' /* 'in' or 'out' (side) the modal */,
                    bindKey: 27 /* Esc */,
                    callback: function(modal) {
                        modal.hide();
                    }
                }
            });
            offlineModal.show();
        }
    }

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
                case 'log':
                    msg = "<span class='log'>" + data + "</span>";
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
                case 'log':
                    msg = "<span class='log'>" + data + "</span>";
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

    var Controller = function(socket) {
        this.socket = socket;
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
                r.add_player(self.get_player(player));
            });
            $.each(room.spectators, function(i, player) {
                r.add_spectator(self.get_player(player));
            });
            return r;
        };

        /**
         * Current player
         */
        this.set_id = function(id) {
            this.id = id;
        };
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
        this.playerConnect = function(data) {
            var player = self.createPlayer(data)
            self.add_player(player);
            self.chat.global_event('connect', player.username);
            player.render();
        };

        this.playerDisconnect = function(id) {
            var player = self.get_player(id);
            self.chat.global_event('disconnect', player.username);
            self.remove_player(id);
            player.unrender();
        };

        this.playerMessage = function(data) {
            switch(data.chat){
                case 'room':
                    self.chat.local_event('message', self.get_player(data.playerid).username, data.msg);
                    break;
                case 'global':
                    self.chat.global_event('message', self.get_player(data.playerid).username, data.msg);
                    break;
                default:
                    console.error("Unknown chat", data);
                    break;
            }
        };

        this.playerJoinRoom = function(data){
            var player = self.get_player(data.playerid),
                room = self.get_room(data.roomid);
            
            player.set_room(room);
            room.add_player(player);

            player.render();
            room.render();

            if(self.id === data.playerid){
                self.chat.local_event('log', "Te has sentado en la mesa "+ room.id);
            }
        };

        this.playerSpectateRoom = function(data){
            var player = self.get_player(data.playerid),
                room = self.get_room(data.roomid);

            player.set_room(room);
            room.add_spectator(player);

            player.render();
            room.render();
            
            if(self.id === data.playerid){
                self.chat.local_event('log', "Estás espectando la mesa "+ room.id);
            }
        };

        this.playerLeaveRoom = function(data){
            var player = self.get_player(data.playerid),
                room = self.get_room(data.roomid);
            
            player.unset_room();
            room.remove_player(data.playerid);
            room.remove_spectator(data.playerid);

            player.render();
            room.render();

            if(self.id === data.playerid){
                self.chat.local_event('log', "Has dejado la mesa "+ room.id);
            }
        };

        this.roomStatusChange = function(data){
            var room = self.get_room(data.roomid);
            room.set_status(data.status);
            room.render();
        }

        this.roomRequestReady = function(time) {
            self.readyModal = new gModal({
                title: 'La partida va a empezar. ¿Estás list@?',
                body: '<div class="ready-timer">' + time + '</div>',
                buttons: [
                    {
                        content: '¡Vamos!',
                        classes: 'btn btn-primary',
                        bindKey: 13 /* Enter */,
                        callback: function(modal) {
                            self.socket.emit('ready');
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
                        self.socket.emit('unready');
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

        this.roomUnready = function() {
            if (typeof self.readyModal != 'undefined') self.readyModal.hide();
            if (typeof self.pendingModal != 'undefined') self.pendingModal.hide();
        };

        this.play = function() {
            if (typeof self.readyModal != 'undefined') self.readyModal.hide();
            if (typeof self.pendingModal != 'undefined') self.pendingModal.hide();

            $('#loading').hide();
            $('#rooms').hide();
            $('#play').show();

            self.socket.emit('action', { action: 'ack', event: 'play' });
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

            self.socket.emit('join', { room: targetRoom.id });
        };

        this.leaveRoom = function() {
            self.socket.emit('leave', { room: self.room().id });
        };

        this.spectateRoom = function() {
            var targetRoom = self.get_room($(this).data('room'));
            var currentRoom = self.room();

            if (currentRoom !== null) {
                self.leaveRoom();
            }

            self.socket.emit('spectate', { room: targetRoom.id });
        };

        this.render = function() {
            $.each(this.rooms, function(id, room) {
                room.render();
            });
            $.each(this.players, function(id, player) {
                player.render();
            });

            self.chat.global_event('log', "Has entrado en la sala");
            $('#play').hide();
            $('#rooms').css('display', 'flex');

            $('#loading').hide();
            $('#panel').show();
            $('#game').show();
        };
    }

    var Room = function(id, status, numplayers) {
        this.id = id;
        this.status = status;
        this.numplayers = numplayers;
        this.players = {};
        this.spectators = {};

        var STATUS_EMPTY = 0,
            STATUS_WAITING = 1,
            STATUS_READY = 2,
            STATUS_PLAYING = 3;

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
         * Status
         */
        this.set_status = function(status){
            this.status = status;
        }

        /**
         * UI
         */
        this.render = function() {
            if ($('#room_' + this.id).length == 0) {
                $('#rooms').append(
                    "<div id='room_" +
                        this.id +
                        "' class='room col-xl-6 col-lg-6 col-md-12 col-sm-6 col-6'>" +
                        '</div>'
                );
            }
    
            var players_html = '';
            $.each(this.players, function(id, player) {
                players_html += '<p>' + player.username + '</p>';
            });

            var join_disabled = this.status >= STATUS_READY ? " disabled" : "",
                spectate_disabled = this.status != STATUS_PLAYING ? " disabled" : "";
    
            $('#room_' + this.id).html(
                '<div class="room-header">'+
                    'Mesa ' + this.id +
                    '<button class="btn join'+join_disabled+'"'+join_disabled+' data-room="' + this.id + '">Entrar</button>' +
                    '<button class="btn spectate'+spectate_disabled+'"'+spectate_disabled+' data-room="' + this.id + '">Ver</button>' +
                '</div>' +
                "<div class='room-content'>" + players_html + '</div>'
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
            console.log("render", this);
            if ($('#player' + this.id).length == 0) {
                $('#players table tbody').append(
                    "<tr id='player_" + this.id + "' class='player'></tr>"
                );
            }

            var room = this.room === null ? "-" : this.room.id;
            $("#player_"+ this.id).html(
                "<td>" + this.username + "</td>" +
                "<td>" + room + "</td>"
            );
        };

        this.unrender = function(){
            $("#player_"+ this.id).remove();
        }
    }

    return [Utils, Chat, Controller, Room, Player];
})(jQuery)