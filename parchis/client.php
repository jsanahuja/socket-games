<?php
    // Manual, we do not need everything
    require_once("../core/globals.php");
?>
<!doctype html>
<html>

<head>
    <title>Parchis</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link href="assets/css/gModal.min.css" rel="stylesheet" type="text/css" />
    <style>
        .container-fluid{padding:0}
        .row{margin:0;}
        
        #chat {padding: 0;}
        #chat .chat-nav li {cursor: pointer;width: 50%;padding: 5px 0;background: #FFF;text-align: center;border: 1px #000 solid;border-bottom:1px #007bff solid;box-sizing: border-box;}
        #chat .chat-nav li:first-child {border-left: 0;}
        #chat .chat-nav li:last-child {border-right: 0;}
        #chat .chat-nav li.active {border-bottom: 0;border-color:#007bff;color: #007bff;}
        #chat .chat-window {overflow-y: auto;margin:5px;border-top: 0;padding: 0.25em 0.5em;}
        #chat .chat-window::-webkit-scrollbar {width: 5px;background: #CECECE;}
        #chat .chat-window::-webkit-scrollbar-track {}
        #chat .chat-window::-webkit-scrollbar-thumb {background: #4ca2ff;border-radius: 6px;}
        #chat .chat-window::-webkit-scrollbar-thumb:hover {background: #78b9ff;}
        #chat .chat-window::-webkit-scrollbar-track-piece {}
        #chat .chat-window p {margin-bottom: 0;}
        #chat .chat-input {border-top: 0;padding: 0.25em 0.5em;}

        #game {background: #000;padding: 1em 3em;height:100vh;overflow-y: auto;}
        #game::-webkit-scrollbar {width: 12px;background:white;border-left:1px #007bff solid;}
        #game::-webkit-scrollbar-track {border-right: 1px #007bff solid;padding: 2px;}
        #game::-webkit-scrollbar-thumb {background: #4ca2ff;border-radius: 3px;}
        #game::-webkit-scrollbar-thumb:hover {background: #78b9ff;}
        #game::-webkit-scrollbar-track-piece {}

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

        .gmodal-body .ready-timer{text-align: center;font-size: 2em;}

        #game #play{position:relative;margin:10px auto;border-radius:20px;background-color: #FFF;}
        #game #play #square_yellow {position:absolute;width:30%;height:30%;background:#ffff75;left:0px;top:0px;border-top-left-radius:50%}
        #game #play #square_green  {position:absolute;width:30%;height:30%;background:#64e064;right:0px;top:0px;border-top-right-radius:50%}
        #game #play #square_blue   {position:absolute;width:30%;height:30%;background:#3c9cff;left:0px;bottom:0px;border-bottom-left-radius:50%}
        #game #play #square_red    {position:absolute;width:30%;height:30%;background:#ff4b4b;right:0px;bottom:0px;border-bottom-right-radius:50%}

        #game #play #area_top       {z-index:3;position:absolute;width:40%;height:34.28571429%;background:#fff;left:30%;top:0;}
        #game #play #area_right     {z-index:3;position:absolute;width:40%;height:34.28571429%;background:#fff;left:100%;top:30%;transform-origin: top left;transform:rotate(90deg);}
        #game #play #area_bottom    {z-index:3;position:absolute;width:40%;height:34.28571429%;background:#fff;left:70%;top:100%;transform-origin: top left;transform:rotate(180deg);}
        #game #play #area_left      {z-index:3;position:absolute;width:40%;height:34.28571429%;background:#fff;left:0%;top:70%;transform-origin: top left;transform:rotate(-90deg);}

        #game #play .box{float:left;height:12.5%;width:33.3333333%;display:block;}
        #game #play .box.bleft{text-align:right;}
        #game #play .box.bcenter{text-align:center;}
        #game #play .box.bright{text-align:left;}
        #game #play .box span{vertical-align: middle;padding:0 5px;}
        #game #play .yellow{background:#ffff75;}
        #game #play .green{background:#64e064;color:#FFF;}
        #game #play .blue{background:#3c9cff;}
        #game #play .red{background:#ff4b4b;}
        #game #play .lyellow{background:#fdfd94;}
        #game #play .lgreen{background:#b1ffb1;}
        #game #play .lblue{background:#afd6ff;}
        #game #play .lred{background:#ffa6a6;}
        #game #play .box.secure{
            background-image: url(https://games.sowecms.com/parchis/assets/img/circle.svg);
            background-size: auto 80%;
            background-position: center center;
            background-repeat: no-repeat;
            color:#666 !important;
        }

        #game #play #area_center  {position:absolute;width:40.1%;height:40.1%;background:#fff;left:29.95%;top:29.95%;}
        #game #play #area_center .center_center {position:absolute;width:100%;height:100%;transform:rotate(45deg);}
        #game #play #area_center .center_center .yellow {position:absolute;width:50%;height:50%;left:0%;top:0%;}
        #game #play #area_center .center_center .green  {position:absolute;width:50%;height:50%;left:50%;top:0%;}
        #game #play #area_center .center_center .blue   {position:absolute;width:50%;height:50%;left:0%;top:50%;}
        #game #play #area_center .center_center .red    {position:absolute;width:50%;height:50%;left:50%;top:50%;}
        #game #play #area_center .center_center .dices  {position:absolute;width:50%;height:50%;left:25%;top:25%;background:#FFF;border-radius:100%;}
    
        #game #play .chip{position: absolute;z-index:10;top: 48.5%;left: 48.5%;height: 3%;width: 3%;border-radius: 100%;box-shadow: 0px 0px 2px 1px #000;}
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div id="game" class="col-xl-9 col-lg-9 col-md-12 col-sm-12 col-12">
                <div id="play">
                    <div id="square_red"></div>
                    <div id="square_blue"></div>
                    <div id="square_green"></div>
                    <div id="square_yellow"></div>

                    <div id="area_top">
                        <div id="box_1"         class="bleft box"><span>1</span></div>
                        <div id="box_68"        class="bcenter box yellow secure"><span>68</span></div>
                        <div id="box_67"        class="bright box"><span>67</span></div>

                        <div id="box_2"         class="bleft box"><span>2</span></div>
                        <div id="box_69"        class="bcenter box yellow"></div>
                        <div id="box_66"        class="bright box"><span>66</span></div>

                        <div id="box_3"         class="bleft box"><span>3</span></div>
                        <div id="box_70"        class="bcenter box yellow"></div>
                        <div id="box_65"        class="bright box"><span>65</span></div>

                        <div id="box_4"         class="bleft box"><span>4</span></div>
                        <div id="box_71"        class="bcenter box yellow"></div>
                        <div id="box_64"        class="bright box"><span>64</span></div>

                        <div id="box_5"         class="bleft box yellow secure"><span>5</span></div>
                        <div id="box_72"        class="bcenter box yellow"></div>
                        <div id="box_63"        class="bright box lgreen secure"><span>63</span></div>

                        <div id="box_6"         class="bleft box"><span>6</span></div>
                        <div id="box_73"        class="bcenter box yellow"></div>
                        <div id="box_62"        class="bright box"><span>62</span></div>

                        <div id="box_7"         class="bleft box"><span>7</span></div>
                        <div id="box_74"        class="bcenter box yellow"></div>
                        <div id="box_61"        class="bright box"><span>61</span></div>

                        <div id="box_8"         class="bleft box"><span>8</span></div>
                        <div id="box_75"        class="bcenter box yellow"></div>
                        <div id="box_60"        class="bright box"><span>60</span></div>
                    </div>
                    <div id="area_right">
                        <div id="box_52"        class="bleft box"><span>52</span></div>
                        <div id="box_51"        class="bcenter box green secure"><span>51</span></div>
                        <div id="box_50"        class="bright box"><span>50</span></div>

                        <div id="box_53"        class="bleft box"><span>53</span></div>
                        <div id="box_93"        class="bcenter box green"></div>
                        <div id="box_49"        class="bright box"><span>49</span></div>

                        <div id="box_54"        class="bleft box"><span>54</span></div>
                        <div id="box_94"        class="bcenter box green"></div>
                        <div id="box_48"        class="bright box"><span>48</span></div>

                        <div id="box_55"        class="bleft box"><span>55</span></div>
                        <div id="box_95"        class="bcenter box green"></div>
                        <div id="box_47"        class="bright box"><span>47</span></div>

                        <div id="box_56"        class="bleft box green secure"><span>56</span></div>
                        <div id="box_96"        class="bcenter box green"></div>
                        <div id="box_46"        class="bright box lred secure"><span>46</span></div>

                        <div id="box_57"        class="bleft box"><span>57</span></div>
                        <div id="box_97"        class="bcenter box green"></div>
                        <div id="box_45"        class="bright box"><span>45</span></div>

                        <div id="box_58"        class="bleft box"><span>58</span></div>
                        <div id="box_98"        class="bcenter box green"></div>
                        <div id="box_44"        class="bright box"><span>44</span></div>

                        <div id="box_59"        class="bleft box"><span>59</span></div>
                        <div id="box_99"        class="bcenter box green"></div>
                        <div id="box_43"        class="bright box"><span>43</span></div>
                    </div>
                    <div id="area_bottom">
                        <div id="box_35"        class="bleft box"><span>35</span></div>
                        <div id="box_34"        class="bcenter box red secure"><span>34</span></div>
                        <div id="box_33"        class="bright box"><span>33</span></div>

                        <div id="box_36"        class="bleft box"><span>36</span></div>
                        <div id="box_85"    class="bcenter box red"></div>
                        <div id="box_32"        class="bright box"><span>32</span></div>

                        <div id="box_37"        class="bleft box"><span>37</span></div>
                        <div id="box_86"    class="bcenter box red"></div>
                        <div id="box_31"        class="bright box"><span>31</span></div>

                        <div id="box_38"        class="bleft box"><span>38</span></div>
                        <div id="box_87"    class="bcenter box red"></div>
                        <div id="box_30"        class="bright box"><span>30</span></div>

                        <div id="box_39"        class="bleft box red secure"><span>39</span></div>
                        <div id="box_88"    class="bcenter box red"></div>
                        <div id="box_29"        class="bright box lblue secure"><span>29</span></div>

                        <div id="box_40"        class="bleft box"><span>40</span></div>
                        <div id="box_89"    class="bcenter box red"></div>
                        <div id="box_28"        class="bright box"><span>28</span></div>

                        <div id="box_41"        class="bleft box"><span>41</span></div>
                        <div id="box_90"    class="bcenter box red"></div>
                        <div id="box_27"        class="bright box"><span>27</span></div>

                        <div id="box_42"        class="bleft box"><span>42</span></div>
                        <div id="box_91"    class="bcenter box red"></div>
                        <div id="box_26"        class="bright box"><span>26</span></div>
                    </div>
                    <div id="area_left">
                        <div id="box_18"        class="bleft box"><span>18</span></div>
                        <div id="box_17"        class="bcenter box blue secure"><span>17</span></div>
                        <div id="box_16"        class="bright box"><span>16</span></div>

                        <div id="box_19"        class="bleft box"><span>19</span></div>
                        <div id="box_77"    class="bcenter box blue"></div>
                        <div id="box_15"        class="bright box"><span>15</span></div>

                        <div id="box_20"        class="bleft box"><span>20</span></div>
                        <div id="box_78"    class="bcenter box blue"></div>
                        <div id="box_14"        class="bright box"><span>14</span></div>

                        <div id="box_21"        class="bleft box"><span>21</span></div>
                        <div id="box_79"    class="bcenter box blue"></div>
                        <div id="box_13"        class="bright box"><span>13</span></div>

                        <div id="box_22"        class="bleft box blue secure"><span>22</span></div>
                        <div id="box_80"    class="bcenter box blue"></div>
                        <div id="box_12"        class="bright box lyellow secure"><span>12</span></div>

                        <div id="box_23"        class="bleft box"><span>23</span></div>
                        <div id="box_81"    class="bcenter box blue"></div>
                        <div id="box_11"        class="bright box"><span>11</span></div>

                        <div id="box_24"        class="bleft box"><span>24</span></div>
                        <div id="box_82"    class="bcenter box blue"></div>
                        <div id="box_10"        class="bright box"><span>10</span></div>

                        <div id="box_25"        class="bleft box"><span>25</span></div>
                        <div id="box_83"    class="bcenter box blue"></div>
                        <div id="box_9"        class="bright box"><span>9</span></div>
                    </div>
                    <div id="area_center">
                        <div class="center_center">
                            <div id="box_76" class="box yellow"></div>
                            <div id="box_100" class="box green"></div>
                            <div id="box_84" class="box blue"></div>
                            <div id="box_92" class="box red"></div>
                            <div class="dices"></div>
                        </div>
                    </div>

                    <div class="chip yellow" id="chip_yellow_0"></div>
                    <div class="chip yellow" id="chip_yellow_1"></div>
                    <div class="chip yellow" id="chip_yellow_2"></div>
                    <div class="chip yellow" id="chip_yellow_3"></div>
                    
                    <div class="chip blue"   id="chip_blue_0"></div>
                    <div class="chip blue"   id="chip_blue_1"></div>
                    <div class="chip blue"   id="chip_blue_2"></div>
                    <div class="chip blue"   id="chip_blue_3"></div>

                    <div class="chip red"    id="chip_red_0"></div>
                    <div class="chip red"    id="chip_red_1"></div>
                    <div class="chip red"    id="chip_red_2"></div>
                    <div class="chip red"    id="chip_red_3"></div>

                    <div class="chip green"  id="chip_green_0"></div>
                    <div class="chip green"  id="chip_green_1"></div>
                    <div class="chip green"  id="chip_green_2"></div>
                    <div class="chip green"  id="chip_green_3"></div>
                </div>
                <div id="rooms" style="display:none;" class="row"></div>
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
    <script src="assets/js/gModal.min.js"></script>
    <script>
        (function ($) {
            // 2 fix;
            function fix_game_size(){
                var min = Math.min($('#game').height(), $('#game').width()) - 20;

                $("#play").css({
                    "height": min,
                    "width": min
                });
            }
            fix_game_size();
            $(window).on("resize", fix_game_size);



            var ROOM_STATUS_EMPTY = <?php print ROOM_STATUS_EMPTY; ?>,
                ROOM_STATUS_WAITING = <?php print ROOM_STATUS_WAITING; ?>,
                ROOM_STATUS_READY = <?php print ROOM_STATUS_READY; ?>,
                ROOM_STATUS_PLAYING = <?php print ROOM_STATUS_PLAYING; ?>,
                GAME_READY_TIME = <?php print GAME_READY_TIME; ?>;

            var socket = io("https://games.sowecms.com:<?php print PARCHIS_PORT; ?>"),
                players = {},
                rooms = {},
                id,
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
                // "username": prompt("Username")
                "username": "<?php 
                    if(isset($_GET['user']))
                        print $_GET['user'];
                    else 
                        print 'BanNsS1'; 
                    ?>"
            });


            /*********************************
             *********************************
             *****   GAME features
             *********************************
             *********************************/
            function Board(players){
                this.players = players;
                this.colors = {};
                this.boxes = {};

                /**
                 * Adders
                 */
                this.add_color = function(color){
                    this.colors[color.color] = color;
                }
                
                this.add_chip = function(chip){
                    this.colors[chip.color.color].add_chip(chip);
                    chip.go_home();
                }

                this.add_box = function(box){
                    this.boxes[box.id] = box;
                }

                /**
                 * Getters
                 */
                this.get_box = function(id){
                    return this.boxes[id];
                }

                /**
                 * Core
                 */
                this.build = function(){
                    this.add_color(new Color("yellow", this, this.players[0],  5, 68, 69,  76, $("#square_yellow")));
                    this.add_color(new Color("blue",   this, this.players[1], 22, 17, 77,  84, $("#square_blue")));
                    this.add_color(new Color("red",    this, this.players[2], 39, 34, 85,  92, $("#square_red")));
                    this.add_color(new Color("green",  this, this.players[3], 56, 51, 93, 100, $("#square_green")));

                    var chips = $(".chip");
                    for(var i = 0; i < chips.length; i++){
                        var [type, color, id] = $(chips[i]).attr("id").split("_");

                        this.add_chip(
                            new Chip(parseInt(id), this.colors[color], this, $(chips[i]))
                        );
                    }

                    var boxes = $(".box");
                    for(var i = 0; i < boxes.length; i++){
                        
                        var [type, id] = $(boxes[i]).attr("id").split("_");
                        this.add_box(
                            new Box(id, $(boxes[i]))
                        );
                    }
                };

                this.test = function(){
                    var colors = Object.keys(this.colors);
                    var ddo = [];
                    for(var i = 0; i < colors.length; i++){
                        var chip = this.colors[colors[i]].get_chip(0);
                        var a = function(){ 
                            chip.advance(200, a);
                        }
                        chip.advance(200, a);
                    }
                }
            }

            function Box(id, domElement){
                this.id = id;
                this.element = domElement;
                this.rotated = this.element.parent().attr('id') == "area_right" || this.element.parent().attr('id') == "area_left";

                this.get_position = function(){
                    var cOffset = $("#play").offset(),
                        bOffset = this.element.offset();
                    return {
                        x: bOffset.left - cOffset.left + (this.rotated ? this.element.height() : this.element.width())/2,
                        y: bOffset.top  - cOffset.top  + (this.rotated ? this.element.width()  : this.element.height())/2
                    };
                };
            }

            function Color(color, board, player, initial, breaker, postbreak, finish, domElement){
                this.color = color;

                this.initial    = initial;
                this.breaker    = breaker;
                this.postbreak  = postbreak;
                this.finish     = finish;
                
                this.board = board;
                this.player = player;
                this.chips = {};

                this.element = domElement;

                this.add_chip = function(chip){
                    this.chips[chip.id] = chip;
                }
                
                this.get_chip = function(id){
                    return this.chips[id];
                }

                this.get_position = function(){
                    var top = parseInt(domElement.css("top")),
                        left = parseInt(domElement.css("left")),
                        size = 30;

                    return {
                        top:  (top == 0 ? 0 : 70) + size/2,
                        left: (left== 0 ? 0 : 70) + size/2
                    };
                }

                this.get_next = function(position){
                    if(position == -1)
                        return this.initial;
                    if(position === this.breaker)
                        return this.postbreak;
                    if(position == this.finish)
                        return false;
                    if(position == 68)
                        return 1;
                    return position+1;
                }
            }

            function Chip(id, color, board, domElement){
                this.id = id;
                this.position = -1;
                this.color = color;
                this.board = board;
                this.element = domElement;

                // statics
                this.size = 3;

                this.go_home = function(time, callback){
                    var home = this.color.get_position();

                    switch(this.id){
                        case 0:
                            home.top  -= this.size;
                            home.left -= this.size;
                            break;
                        case 1:
                            home.top  -= this.size;
                            home.left += this.size;
                            break;
                        case 2:
                            home.top  += this.size;
                            home.left -= this.size;
                            break;
                        case 3:
                            home.top  += this.size;
                            home.left += this.size;
                            break;
                        default:
                            console.log("Error: id: '"+ id +"' goto_base");
                    }

                    if(typeof time === "undefined" || time == 0){
                        this.element.css({
                            "top":  home.top + "%",
                            "left": home.left + "%"
                        })
                    }else{
                        this.element.animate({
                            "top":  home.top + "%",
                            "left": home.left + "%"
                        }, time, callback);
                    }
                };

                this.advance = function(time, callback){
                    var next = this.color.get_next(this.position);
                    if(next === false)
                        return;
                    var pos = this.board.get_box(next).get_position();
                        move = {
                            top:  pos.y - this.element.width()/2  + "px",
                            left: pos.x - this.element.height()/2 + "px"
                        };

                    if(typeof time === "undefined" || time == 0){
                        this.element.css(move);
                    }else{
                        this.element.animate(move, time, callback);
                    }
                    this.position = next;

                }
            }


            function test(){
                var board = new Board([players[id], {}, {}, {}]);
                board.build();
                board.test();
            }


            test();

            /*********************************
             *********************************
             *****   READY features
             *********************************
             *********************************/

            var readyModal, pendingModal;
            socket.on('ready', function(){
                readyModal = new gModal({
                    title: "La partida va a empezar. ¿Estás list@?",
                    body: "<div class=\"ready-timer\">"+ GAME_READY_TIME +"</div>",
                    buttons: [{
                        content: "¡Vamos!",
                        classes: "gmodal-button-blue",
                        bindKey: 13, /* Enter */
                        callback: function(modal){
                            socket.emit("ready");
                            modal.hide();
                            pendingModal = new gModal({
                                title: "La partida va a empezar",
                                body: "<center>Esperando al resto de jugadores...</center>",
                                close: { closable: false }
                            });
                            pendingModal.show();
                        }
                    }],
                    close: {
                        closable: true,
                        location: "in", /* 'in' or 'out' (side) the modal */
                        bindKey: 27, /* Esc */
                        callback: function(modal){
                            socket.emit("unready");
                            modal.hide();
                        }
                    },
                    onShow: function(modal){
                        var time = GAME_READY_TIME;
                        var interval = setInterval(function(){
                            --time;
                            $(".ready-timer").text(time);
                            
                            if(time <= 0)
                                clearInterval(interval);
                        }, 1000);
                    },
                });
                readyModal.show();
            });

            socket.on('unready', function(){
                readyModal.hide();
                pendingModal.hide();
            });

            socket.on('play', function(){
                readyModal.hide();
                pendingModal.hide();
                $("#rooms").hide();
                $("#play").show();


            });


            /*********************************
             *********************************
             *****   HUD features
             *********************************
             *********************************/
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
                    render_player(data.id);
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
                    id = data.you;
                    players = data.players;
                    rooms = data.rooms;
                    render();
                }
                console.log(data);
            });

            socket.on("update_player", function(player){
                players[player.id] = player;
                render_player(player.id);
                console.log("UP Player ", player);
            });
            
            socket.on("update_room", function(room){
                rooms[room.id] = room;
                render_room(room.id);
                console.log("UP Room", room);
            });

            /*********************************
             *********************************
             *****   ROOM features
             *********************************
             *********************************/
            $(document).on("click", ".join_room", function(){
                var id = $(this).data("room"),
                    room = rooms[id];
                
                if(room.status === ROOM_STATUS_READY)
                    return;

                if(room.status === ROOM_STATUS_PLAYING){
                    socket.emit("room_spectate", {
                        room: id
                    });
                }else if(
                    (room.status === ROOM_STATUS_EMPTY) ||
                    (room.status === ROOM_STATUS_WAITING && room.numplayers > Object.keys(room.players).length)
                ){
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