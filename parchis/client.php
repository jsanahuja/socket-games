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
    <link href="/assets/gModal/dist/gModal.min.css" rel="stylesheet" type="text/css" />
    <link href="/assets/fontello/css/icons.css" rel="stylesheet" type="text/css" />

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

        #game {background: #ececec;padding: 1em 3em;height:100vh;overflow-y: auto;}
        #game::-webkit-scrollbar {width: 12px;background:white;border-left:1px #007bff solid;}
        #game::-webkit-scrollbar-track {border-right: 1px #007bff solid;padding: 2px;}
        #game::-webkit-scrollbar-thumb {background: #4ca2ff;border-radius: 3px;}
        #game::-webkit-scrollbar-thumb:hover {background: #78b9ff;}
        #game::-webkit-scrollbar-track-piece {}

        #game #loading .loading{display: inline-block;position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);}

        #game #rooms{display:none;}
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

        #game #play{display:none;position:relative;margin:10px auto;border-radius:20px;background-color: #FFF;}
        #game #play #square_yellow {position:absolute;width:30%;height:30%;background:#dad05f;background: linear-gradient(315deg, #dad05f 0%, #e9dd7f 100%);left:0px;top:0px;border-bottom-right-radius:6px;}
        #game #play #square_green  {position:absolute;width:30%;height:30%;background:#92c348;background: linear-gradient( 45deg, #92c348 0%, #a0cc6a 100%);right:0px;top:0px;border-bottom-left-radius:6px;}
        #game #play #square_blue   {position:absolute;width:30%;height:30%;background:#69a5d2;background: linear-gradient(225deg, #69a5d2 0%, #7db2d4 100%);left:0px;bottom:0px;border-top-right-radius:6px;}
        #game #play #square_red    {position:absolute;width:30%;height:30%;background:#e7413c;background: linear-gradient(135deg, #e7413c 0%, #e99c9b 100%);right:0px;bottom:0px;border-top-left-radius:6px;}

        #game #play .user{display:none;position: absolute;background: #FFF;border: 2px #fff solid;border-radius: 3px;}
        #game #play .user .profilepic{height: 60px;width: 60px;background: #CCC;display: inline-block;vertical-align: middle;}
        #game #play .user .username{display: inline-block;padding: 0px 0.5em;}

        #game #play #square_yellow .user{top:0; left:0;}
        #game #play #square_green  .user{top:0; right:0;}
        #game #play #square_blue   .user{bottom:0; left:0;}
        #game #play #square_red    .user{bottom:0; right:0;}

        #game #play #area_top       {z-index:3;position:absolute;width:40%;height:34.28571429%;left:30%;top:0;}
        #game #play #area_right     {z-index:3;position:absolute;width:40%;height:34.28571429%;left:100%;top:30%;transform-origin: top left;transform:rotate(90deg);}
        #game #play #area_bottom    {z-index:3;position:absolute;width:40%;height:34.28571429%;left:70%;top:100%;transform-origin: top left;transform:rotate(180deg);}
        #game #play #area_left      {z-index:3;position:absolute;width:40%;height:34.28571429%;left:0%;top:70%;transform-origin: top left;transform:rotate(-90deg);}

        #game #play .box{float:left;height:12.5%;width:33.3333333%;display:block;background:#FFF;}
        #game #play .box.bleft{text-align:right;}
        #game #play .box.bcenter{text-align:center;}
        #game #play .box.bright{text-align:left;}
        #game #play .box span{vertical-align: middle;padding:0 5px;}
        #game #play .yellow{background:#e8ca3f;}
        #game #play .green{background:#69a63b;}
        #game #play .blue{background:#4b96bf;}
        #game #play .red{background:#e7413c;}
        #game #play .yellow {background:#e8ca3f;background: linear-gradient(315deg, #e8ca3f 0%, #ece698 100%);}
        #game #play .green  {background:#69a63b;background: linear-gradient(45deg,  #94c44c 0%, #a4d855 100%);}
        #game #play .blue   {background:#4b96bf;background: linear-gradient(225deg, #4b96bf 0%, #7bb6e2 100%);}
        #game #play .red    {background:#e7413c;background: linear-gradient(135deg, #e7413c 0%, #ff7672 100%);}
        #game #play .secure.yellow {background:#e8ca3f;}
        #game #play .secure.green  {background:#94c44c;}
        #game #play .secure.blue   {background:#4b96bf;}
        #game #play .secure.red    {background:#e7413c;}
        #game #play .lyellow{background:#e9dd7f;}
        #game #play .lgreen{background:#a0cc6a;}
        #game #play .lblue{background:#7db2d4;}
        #game #play .lred{background:#e99c9b;}
        #game #play .box.secure{
            background-image: url(/assets/parchis/circle.svg);
            background-size: auto 80%;
            background-position: center center;
            background-repeat: no-repeat;
            color:#666 !important;
        }
        #game #play .box.active{cursor:pointer;animation: orangting 1s infinite;transform-origin: center;}

        #game #play #area_center  {position:absolute;width:40.1%;height:40.1%;background:#fff;left:29.95%;top:29.95%;overflow:hidden;}
        #game #play #area_center .center_center {position:absolute;width:100%;height:100%;transform:rotate(45deg);}
        #game #play #area_center .center_center .yellow {position:absolute;width:100%;height:100%;left:-25%;top:-25%;}
        #game #play #area_center .center_center .green  {position:absolute;width:100%;height:100%;left:50%;top:-50%;}
        #game #play #area_center .center_center .blue   {position:absolute;width:100%;height:100%;left:-50%;top:50%;}
        #game #play #area_center .center_center .red    {position:absolute;width:100%;height:100%;left:50%;top:50%;}
        #game #play #area_center .center_center .dices  {position:absolute;width:50%;height:50%;left:25%;top:25%;background:#FFF;border-radius:100%;transform:rotate(-45deg);}
        #game #play #area_center .center_center .dices div{position: absolute;width: 25%;height: 25%;top: 50%;transform: translate(-50%, -50%);background-size: 100% 100%;background-repeat: no-repeat;background-position: center center;}
        #game #play #area_center .center_center .dices #dice1{left:  25%;transform: translate(-50%, -50%);}
        #game #play #area_center .center_center .dices #dice2{right: 25%;transform: translate( 50%, -50%);}
        #game #play #area_center .center_center .dices.active{cursor:pointer;animation: dicesbeating 1s infinite;transform-origin: center;}

        
        #game #play .chip{position: absolute;display:none;z-index:10;top: 48.5%;left: 48.5%;height: 3%;width: 3%;border-radius: 100%;box-shadow: 0px 0px 2px 1px #000;}
        #game #play .chip.active{cursor:pointer;animation: chipbeating 1s infinite;transform-origin: center;}
        
        @keyframes dicesbeating {
            from { transform: rotate(-45deg); }
            50% { transform: rotate(-45deg) scale(1.1); }
            to { transform: rotate(-45deg); }
        }
        @keyframes chipbeating {
            from {}
            50% { transform: scale(1.2); }
            to {}
        }
        @keyframes orangting {
            from {}
            50% {background: orange;}
            to {}
        }        
        </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div id="game" class="col-xl-9 col-lg-9 col-md-12 col-sm-12 col-12">
                <div id="loading" class="row">
                    <div class="loading">
                        <img src="/assets/images/loading.svg" alt="Spinner loading" />
                        <p>Conectado con el servidor...</p>
                    </div>
                </div>
                <div id="play">
                    <div id="square_red"><div class="user"><div class="profilepic"></div><span class="username"></span></div></div>
                    <div id="square_blue"><div class="user"><div class="profilepic"></div><span class="username"></span></div></div>
                    <div id="square_green"><div class="user"><div class="profilepic"></div><span class="username"></span></div></div>
                    <div id="square_yellow"><div class="user"><div class="profilepic"></div><span class="username"></span></div></div>

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
                            <div class="dices">
                                <div id="dice1"></div>
                                <div id="dice2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="chip yellow" draggable="true" id="chip_yellow_0"></div>
                    <div class="chip yellow" draggable="true" id="chip_yellow_1"></div>
                    <div class="chip yellow" draggable="true" id="chip_yellow_2"></div>
                    <div class="chip yellow" draggable="true" id="chip_yellow_3"></div>
                    <div class="chip blue"   draggable="true" id="chip_blue_0"></div>
                    <div class="chip blue"   draggable="true" id="chip_blue_1"></div>
                    <div class="chip blue"   draggable="true" id="chip_blue_2"></div>
                    <div class="chip blue"   draggable="true" id="chip_blue_3"></div>
                    <div class="chip red"    draggable="true" id="chip_red_0"></div>
                    <div class="chip red"    draggable="true" id="chip_red_1"></div>
                    <div class="chip red"    draggable="true" id="chip_red_2"></div>
                    <div class="chip red"    draggable="true" id="chip_red_3"></div>
                    <div class="chip green"  draggable="true" id="chip_green_0"></div>
                    <div class="chip green"  draggable="true" id="chip_green_1"></div>
                    <div class="chip green"  draggable="true" id="chip_green_2"></div>
                    <div class="chip green"  draggable="true" id="chip_green_3"></div>
                </div>
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
    <script>
        var PORT = <?php print PARCHIS_PORT; ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.2.0/socket.io.js"></script>
    <script src="/assets/gModal/dist/gModal.min.js"></script>
    <script src="/assets/parchis/game.js"></script>
</body>

</html>