(function($, id, username, token, port) {
    /**
     * GAME 
     * SPECITIC
     */

    Controller.prototype.set_game = function(game) {
        this.game = game;

        $('.dices').on('click', this.game.throw_dices);
        $('.chip').on('click', this.game.start_move);
        $('#play').on('mousemove', this.game.on_move);
        $('.box').on('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            game.make_move($(this));
            return false;
        });

        // $('.chip').on('dragstart', function(e){
        //     e.preventDefault();
        //     e.stopPropagation();
        //     $(this).trigger("click");
        //     return false;
        // });
        
        // $('.chip').on('drag', function(e){
        //     e.preventDefault();
        //     e.stopPropagation();
        //     return false;
        // });
        
        // $('.box').on('dragover', function(e) {
        //     e.preventDefault();
        //     e.stopPropagation();
        //     return false;
        // });

        $('.chip').on('mouseover', function(e){
            self.game.highlight_moves($(this))
        });
        $('.chip').on('mouseout', this.game.unhighlight_moves);

        this.socket.on('dices', this.game.request_dices);
        this.socket.on('info_dices', this.game.info_dices);
        this.socket.on('move', this.game.request_move);
        this.socket.on('skip_move', this.game.skip_move);
        this.socket.on('skip_double', this.game.skip_double);
        this.socket.on('info_move', this.game.confirm_move);
        this.socket.on('info_die', this.game.confirm_die);
    };

    Controller.prototype.unset_game = function() {
        this.game = undefined;
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

    var Chip = function(id, position, color, game, domElement) {
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
                    this.go_to(this.position, side, 75, callback);
                    break;
                } else {
                    this.go_to(this.position, false, 75);
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
            if(this.position == -1){
                this.go_home(500);
            }else{
                this.go_to(this.position, false);
            }
            this.element.css('display', 'block');
        };
    }

    var Game = function(id) {
        this.id = id;

        this.players = {};
        this.turn = null;
        this.moves = [];
        this.dices = [];

        var self = this;
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
            $.each(self.players, function(i, player) {
                player.highlight(self.my_turn());
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
            self.turn = playerid;
            self.moves = [];
            self.dices = [];
            if (self.my_turn()) {
                $('.dices').addClass('active');

                // @TODO: REMOVE
                // self.throw_dices();
            }
            self.highlight_turn();
        };

        this.throw_dices = function() {
            if (self.my_turn() && self.dices.length == 0) {
                audio.dices.play();
                socket.emit('action', { action: 'dices' });
                $('.dices').removeClass('active');
            }
        };

        this.info_dices = function(dices) {
            if (!self.my_turn()) {
                audio.dices.play();
            }
            self.dices = dices;
            $('#dice1').removeClass('used');
            $('#dice2').removeClass('used');
            $('#dice1').css('background-image', "url('/assets/parchis/dice" + dices[0] + ".svg')");
            $('#dice2').css('background-image', "url('/assets/parchis/dice" + dices[1] + ".svg')");
        };

        /**
         * Moves
         */

        this.request_move = function(data) {
            if (data.dices.indexOf(self.dices[0])) {
                $('#dice1').removeClass('used');
            } else {
                $('#dice1').addClass('used');
            }
            if (data.dices.indexOf(self.dices[1])) {
                $('#dice2').removeClass('used');
            } else {
                $('#dice2').addClass('used');
            }

            self.turn = data.id;
            if (self.my_turn()) {
                self.moves = data.moves;
                self.players[id].highlight_chips(true, self.moves);

                // @TODO: Remove
                // socket.emit('action', { action: 'move', id: data.moves[0][0], to: data.moves[0][1] });
            }
        };

        this.unhighlight_moves = function() {
            $('.box').removeClass('active');
        };

        this.highlight_moves = function(chip) {
            $('.box').removeClass('active');

            if (self.my_turn()) {
                var [type, color, cid] = chip.attr('id').split('_');

                if (color != self.players[id].color.name) return;

                for (var i = 0; i < self.moves.length; i++) {
                    if (self.moves[i][0] == cid) {
                        $('#box_' + self.moves[i][1]).addClass('active');
                    }
                }
            }
        };

        this.dragchip = false;
        this.start_move = function(e) {
            var chip = $(this);

            // Get target chip
            var [type, color, cid] = chip.attr('id').split('_');
            var targetChip = self.get_player(self.id).chips[cid];
            
            // Use target as TO if already dragging.
            if(self.dragchip !== false){
                self.make_move($("#box_" + targetChip.position));
                return;
            }

            // Cancel previous move else
            self.cancel_move(true);

            if (!self.my_turn()) {
                self.dragchip = false;
            } else {
                if (self.get_player(self.id).color.name === color) {
                    self.dragchip = targetChip;
                    self.dragchip.element.addClass("dragging");
                } else {
                    self.dragchip = false;
                }
            }
        };

        this.on_move = function(e){
            if(self.dragchip !== false){
                self.highlight_moves(self.dragchip.element);
                self.dragchip.element.css({
                    left: e.pageX - $("#play").offset().left - self.dragchip.element.width()/2,
                    top:  e.pageY - $("#play").offset().top - self.dragchip.element.height()/2
                });
            }
        }

        this.cancel_move = function(render){
            if(self.dragchip !== false){
                self.dragchip.element.removeClass("dragging");
                if(render){
                    self.dragchip.render();
                }
                self.unhighlight_moves();
                self.dragchip = false;
            }
        }

        this.make_move = function(box) {
            if (self.dragchip === false) {
                return false;
            }

            if (!self.my_turn()) {
                return false;
            }

            var to = box.attr('id').split('_')[1],
                cid = self.dragchip.id;

            for (var i = 0; i < self.moves.length; i++) {
                if (self.moves[i][0] == cid && self.moves[i][1] == to) {
                    socket.emit('action', { action: 'move', id: cid, to: to });
                    self.cancel_move(false);
                    return;
                }
            }
            self.cancel_move(true);
        };

        this.confirm_move = function(data) {
            var player = self.get_player(data.id);

            if (data.id == self.id) {
                player.highlight_chips(false);
                player.highlight(false);
            }

            var chip = player.get_chip(data.chip),
                from = chip.position,
                side = false;

            $.each(self.players, function(pid, player) {
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
            self.get_player(data.id).get_chip(data.chip).move(-1);
        };

        this.get_box_position = function(bid, side) {
            var rotated = (bid >= 9 && bid <= 25) || (bid >= 43 && bid <= 59),
                box = $('#box_' + bid);
                var pos = {
                    left:
                        box.offset().left -
                        $('#play').offset().left +
                        (rotated ? $("#box_1").height() : $("#box_1").width()) / 2 -
                        $('.chip').width() / 2,
                    top:
                        box.offset().top -
                        $('#play').offset().top +
                        (rotated ? $("#box_1").width() : $("#box_1").height()) / 2 -
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
    audio.dices.loop = false;
    audio.chip.loop = false;

    socket.on('connect', function() {
        if (connected) {
            Utils.reconnect();
            return;
        }
        connected = true;
        authenticated = false;
        controller = new Controller(socket);

        socket.on('successAuth', function(data) {
            authenticated = true;

            controller.set_id(data.id);

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
            socket.on('playerMessage', controller.playerMessage);

            socket.on('playerJoinRoom', controller.playerJoinRoom);
            socket.on('playerSpectateRoom', controller.playerSpectateRoom);
            socket.on('playerLeaveRoom', controller.playerLeaveRoom);
            
            socket.on('roomStatusChange', controller.roomStatusChange);

            $(document).on('click', '.join', controller.joinRoom);
            $(document).on('click', '.spectate', controller.spectateRoom);
            $(document).on('click', '.leave', controller.leaveRoom);

            socket.on('ready', controller.roomRequestReady);
            socket.on('unready', controller.roomUnready);

            socket.on('play', function(data) {
                console.log(data);
                game = new Game(controller.id);
                $.each(data.players, function(i, player) {
                    var playerObject = controller.get_player(player.id);
                    var color = new Color(
                        player.color.id,
                        player.color.name,
                        player.color.initial,
                        player.color.breaker,
                        player.color.postbreak,
                        player.color.finish,
                        $('#square_' + player.color.name)
                    );

                    playerObject.set_color(color);
                    playerObject.init_chips();

                    $.each(player.chips, function(j, chip) {
                        var chip = new Chip(
                            chip.id,
                            chip.position,
                            color,
                            game,
                            $('#chip_' + color.name + '_' + chip.id)
                        );
                        chip.render();
                        playerObject.add_chip(chip);
                    });

                    game.add_player(playerObject);
                    playerObject.display();
                });

                controller.set_game(game);
                controller.play();
            });

            $(window).on('resize', function(e) {
                // Play height
                // var min = Math.min($('#game').height(), $('#game').width()) - 20;

                var min = Math.min(800, $("body").height());

                $('#play').css({ height: min, width: min });
            });
            $(window).trigger('resize');
        });

        // @TODO: Authentication
        socket.emit('auth', {
            id: id,
            username: username,
            token: token
        });
    });

    setTimeout(function(){
        if(!connected){
            Utils.offline();
        }
    }, 5000);

    socket.on('disconnected', function() {
        socket.emit('disconnect');
    });
})(jQuery, id, username, token, port);