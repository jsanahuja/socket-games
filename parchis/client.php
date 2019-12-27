<?php
    require_once("../config.php");
    require_once("defines.php");
?>
<!doctype html>
<html>

<head>
    <title>Parchis</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        .container-fluid{padding:0}
        .row{margin:0;}

        #game {
            background: #000;
            padding: 0;
            height:100vh;
            overflow-y: auto;
        }

        #game #rooms{}
        #game #rooms .room{padding:0.25em;}
        #game #rooms .room .room-header{
            padding: 0.5em 1em;
            font-weight: bold;
            color: #e8e8e8;background: linear-gradient(#6a91ff, #445bf9);
            text-transform: uppercase;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        #game #rooms .room .room-content{
            min-height:200px;
            background:#FFF;
        }

        #chat {
            padding: 0;
        }

        #chat ul.chat-nav {}

        #chat .chat-nav li {
            cursor: pointer;
            width: 50%;
            padding: 5px 0;
            background: #FFF;
            text-align: center;
            border: 1px #000 solid;
            border-bottom:1px #007bff solid;
            box-sizing: border-box;
        }

        #chat .chat-nav li:first-child {
            border-left: 0;
        }

        #chat .chat-nav li:last-child {
            border-right: 0;
        }

        #chat .chat-nav li.active {
            border-bottom: 0;
            border-color:#007bff;
            color: #007bff;
        }

        #chat .chat-window {
            overflow-y: auto;
            border-top: 0;
            padding: 0.25em 0.5em;
        }

        #chat .chat-window p {
            margin-bottom: 0;
        }

        #chat .chat-input {
            border-top: 0;
            padding: 0.25em 0.5em;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div id="game" class="col-xl-9 col-lg-9 col-md-12 col-sm-12 col-12">
                <div id="rooms" class="row"></div>
            </div>

            <div id="chat" class="col-xl-3 col-lg-3 col-md-12 col-sm-12 col-12">
                <ul class="chat-nav nav">
                    <li class="chat-tab" data-nav="global">Global</li>
                    <li class="chat-tab" data-nav="room">Room</li>
                </ul>
                <div class="chat-window d-none" data-nav="global"></div>
                <div class="chat-window d-none" data-nav="room"></div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Mensaje..." id="chat-message" />
                        <div class="input-group-append">
                            <input type="submit" id="chat-submit" class="btn btn-outline-primary" value="Enviar" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



<!--    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>-->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.2.0/socket.io.js"></script>
    <script>
        (function ($) {
            var socket = io("https://games.sowecms.com:<?php print PARCHIS_PORT; ?>"),
                players = {},
                rooms = {},
                connected = false;

            socket.on("connect", function(){
                if(connected)
                    location.reload();
                connected = true;
            });
            
            socket.on('disconnected', function() {
                socket.emit('disconnect');
            });

            socket.emit("login", {
                "username": prompt("Username")
                // "username": "BanNsS1"
            });




            function render(){
                var keys = Object.keys(rooms);
                for(var i = 0; i < keys.length; i++){
                    render_room(keys[i]);
                }
                keys = Object.keys(players);
                for(var i = 0; i < keys.length; i++){
                    render_player(keys[i]);
                }
            }

            function render_room(id){
                // From scratch
                if($("#room_" + id).length == 0){
                    $("#rooms").append(
                        "<div id='room_" + id + "' class='room col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12'>"+
                        "</div>"
                    );
                }

                var players_html = "",
                    keys = Object.keys(rooms[id].players);
                for(var i = 0; i < keys.length; i++){
                    players_html += "<p>" + rooms[id].players[keys[i]].username + "</p>";
                }

                $("#room_" + id).html(
                    "<div class='room-header'>Mesa "+ id + "</div>"+
                    "<div class='room-content'>"+
                        "<button class='join_room' data-room='" + id + "'>Entrar</button>"+
                        players_html +
                    "</div>"
                );
            }

            function render_player(id){

            }

            function unrender_player($id){

            }

            /*********************************
             *********************************
             *****   DATA features
             *********************************
             *********************************/
            
            // User connect messages
            socket.on("user_login", function (data) {
                if(typeof data.username !== "undefined"){
                    $('.chat-window[data-nav="global"]').append("<p><strong>" + data.username + "</strong> se ha conectado.</p>");
                    players[data.id] = data;
                    render_player(player.id);
                }
            });
            
            // User disconnect messages
            socket.on("user_logout", function (data) {
                if(typeof data.username !== "undefined"){
                    $('.chat-window[data-nav="global"]').append("<p><strong>" + data.username + "</strong> se ha desconectado.</p>");
                    delete players[data.id];
                    unrender_player(data.id);
                }
            });

            socket.on("data", function(data){
                if(typeof data.players !== "undefined" && typeof data.rooms !== "undefined"){
                    players = data.players;
                    rooms = data.rooms;
                    render();
                }
                console.log(players, "x", rooms);
            });

            socket.on("update_player", function(player){
                players[player.id] = player;
                render_player(player.id);
                console.log(players[player.id]);
            });
            socket.on("update_room", function(room){
                rooms[room.id] = room;
                render_room(room.id);
                console.log(rooms[room.id]);
            });

            /*********************************
             *********************************
             *****   ROOM features
             *********************************
             *********************************/

            var ROOM_STATUS_EMPTY = <?php print ROOM_STATUS_EMPTY; ?>,
                ROOM_STATUS_WAITING = <?php print ROOM_STATUS_EMPTY; ?>,
                ROOM_STATUS_PLAYING = <?php print ROOM_STATUS_PLAYING; ?>,
                ROOM_MODE_INDIVIDUAL = <?php print ROOM_MODE_INDIVIDUAL; ?>,
                ROOM_MODE_2V2 = <?php print ROOM_MODE_2V2; ?>;

            $(document).on("click", ".join_room", function(){
                var id = $(this).data("room"),
                    room = rooms[id];
                
                if(room.status === ROOM_STATUS_PLAYING){
                    socket.emit("room_spectate", {
                        room: id
                    });
                }else if(room.max_players > Object.keys(room.players).length){
                    socket.emit("room_join", {
                        room: id
                    });
                }
            });
            $(document).on("click", ".leave_room", function(){
                var id = $(this).data("room"),
                    room = rooms[id];
                
                socket.emit("room_leave", {
                    room: id
                });
            });

            /*********************************
             *********************************
             *****   CHAT features
             *********************************
             *********************************/
            // Tab switching
            var chat_tab = "global";
            $('.chat-tab').on("click", function () {
                chat_tab = $(this).data("nav");
                $('.chat-tab').removeClass("active")
                $(this).addClass("active");

                $('.chat-window').addClass("d-none");
                $('.chat-window[data-nav="' + chat_tab + '"]').removeClass("d-none");
            });
            $('.chat-tab[data-nav="' + chat_tab + '"').trigger("click");

            // Height
            function fix_chat_height() {
                $('.chat-window').css("height", $(window).height() - $('.chat-nav').height() - $('.chat-input').height() - 20 + "px");
            }
            $(window).on("resize", fix_chat_height);
            fix_chat_height();

            // Send messages
            function sendMessage() {
                var msg = $("#chat-message").val().trim();
                if (msg.length == 0)
                    return;
                
                socket.emit("message", {
                    "chat": chat_tab,
                    "msg": msg
                });
                $("#chat-message").val("");
            }
            $("#chat-submit").on("click", sendMessage);
            $("#chat-message").on("keypress", function (e) {
                if (!e) e = window.event;
                var keyCode = e.keyCode || e.which;
                if (keyCode == '13') {
                    e.preventDefault();
                    e.stopPropagation();
                    sendMessage();
                    return false;
                }
            });

            // Receive messages
            socket.on("message", function (data) {
                console.log(data);
                if (typeof data.chat !== "undefined" && typeof data.username !== "undefined" && typeof data.msg !== "undefined") {
                    
                    var chat_window = $('.chat-window[data-nav="' + data.chat + '"]');
                    chat_window.append("<p><strong>" + data.username + "</strong>: " + data.msg + "</p>")
                    chat_window[0].scrollTo(0, $(chat_window).height());
                }
            })
        })(jQuery);
    </script>
</body>

</html>