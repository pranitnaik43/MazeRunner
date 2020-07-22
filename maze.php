<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>

    <style>
    #mazeContainer{
        margin-top: 30px;
        position: absolute;
        /* display:block; */
        width: 40%;
        left: 0%;
    }
    #leaderboardContainer{
        margin-top: 30px;
        position: absolute;
        /* background-color: tomato; */
        left: 50%;
        width: 40%;
        border: 3px solid #73AD21;
        /* padding: 10px; */
    }

    </style>

</head>

<body>

    <div class="container" id="mazeContainer">
        <!-- <div class="justify-content-center"> -->
            <form action="">				
                <div class="form-group">
                    <label>Username</label>
                    <div id="usernamelabel"></div>
                    <input type="text" name="username" id = "username" class="form-control" required="required">
                    <!-- <span id="inputError" style="color:red;"></span> -->
                </div>
                <input type="submit" id="loginBtn" class="btn btn-primary" value="Play">
            </form>

            <div id="timer"></div>
            <div id="moves"></div>
            <div id="mazeBody">
                <canvas id="myCanvas" width="600" height="600" style="border:1px solid #d3d3d3;"></canvas>
            </div>
            <h1><div id="msg"></div></h1>
            <h2><div id="score"></div><h2>
        <!-- </div> -->
    </div>

    <?php
    $conn = new mysqli("localhost", "root", "", "maze");
    if ($conn->connect_error) {
        return "Connection failed: " . $conn->connect_error;
    }
    $sql = "SELECT * from highscore";
    $result = $conn->query($sql);

    if($result->num_rows > 0){
        $board = array();
        while($row = $result->fetch_assoc()){
            array_push($board, $row);
        }
    }else{
        return NULL;
    }
    $len = count($board);
    echo '<script>console.log('.json_encode($board).')</script>';
    for($i=0; $i<$len-1; $i++){
        $max = $i;
        for($j=$i+1; $j<$len; $j++){
            if($board[$j]['HighScore']>$board[$max]['HighScore']){
                $max = $j;
            }
        }
        $temp = $board[$max];
        $board[$max] = $board[$i];
        $board[$i] = $temp;
    }
    echo '<script>console.log('.json_encode($board).')</script>';

    $conn->close();
    ?>

    <div class="container" id="leaderboardContainer">
    <table class="table table-striped">
        <h1>Leader Board</h1>
        <thead>
        <tr>
            <th>Username</th>
            <th>High Score</th>
        </tr>
        </thead>
        <tbody>
        <?php 
            for($i=0; $i<count($board) && $i<5; $i++){
                echo '<tr>
                    <td>'.$board[$i]["username"].'</td>
                    <td>'.$board[$i]["HighScore"].'</td>
                </tr>';
            }
        ?>
        </tbody>
  </table>
    </div>

    <script>
    $(document).ready(function(){

    var maze1 = new Array(new Array(13, 6, 12, 7),new Array(14, 10, 10, 15),new Array(8, 0, 1, 6),new Array(11, 9, 7, 11));
    var username;
    var work = 0;

    $('#mazeBody').hide();
    $('#loginBtn').click(function(e){
        if($('#username').text()!==''){
            work = 1;
            e.preventDefault();
            $('#mazeBody').show();
            username = $('#username').val();
            $('#usernamelabel').text(username);
            $('#username').remove();
            $('#loginBtn').remove();
        }
    });

    var width = 100;
    var runnerX = 150;
    var runnerY = 150;
    indRow = 0;
    indCol = 0;
    var runnerRadius =  25;
    var N = 4;  
    var startPt = 100;  
    var undoStack = new Array();
    var redoStack = new Array();
    var moves=0;

    drawMaze();    
    drawRunner(runnerX, runnerY);
    drawFinish();
    displayMoves();

    function endGame(msg){
        // alert("ended");
        $('#mazeBody').hide();
        $('#msg').text(msg);
        var score = 106-moves;
        if(score<0){
            score=0
        }
        $('#score').text("Score: "+score);
        $.ajax({
        url: 'save_score.php',
        type: 'POST',
        dataType: "json",
        data: {
            name: username,
            highscore: score
        },
        success:function(response){
           console.log("done");
       },
        error: function (response) {
            console.log(response);
        }
        });
    }
       
    graph = []
    var path = [];
    for(var i=0; i<N*N; i++){
        graph.push([]);
        for(var j=0; j<N*N; j++){
            graph[i].push(0);
        }
    }
    createGraph();

    function drawMaze(){
        var c = document.getElementById("myCanvas");
        var ctx = c.getContext("2d");
        ctx.beginPath();
        var x=startPt;
        var y=startPt;
        ctx.moveTo(x, y);

        // bottom-1, right-2, upper-4, left-8
        for(var i=0; i<4; i++){
            for(var j=0; j<4; j++){
                var val = maze1[i][j]         
                if(val>=8){                  //left
                    ctx.moveTo(x, y)
                    ctx.lineTo(x,y+width);
                    ctx.stroke();
                    val-=8;        
                }
                if(val>=4){             //upper
                    ctx.moveTo(x, y)
                    ctx.lineTo(x+width,y);
                    ctx.stroke();
                    val-=4;  
                }
                if(val>=2){             //right
                    ctx.moveTo(x+width, y)
                    ctx.lineTo(x+width,y+width);
                    ctx.stroke();
                    val-=2;
                }
                if(val>=1){             //bottom
                    ctx.moveTo(x, y+width)
                    ctx.lineTo(x+width,y+width);
                    ctx.stroke();
                    val-=1;
                }
                x+=width;
            }
            x=startPt;
            y+=width;
        }
    }

    function drawFinish(){
        var finishXY = startPt + (N-1) * width;
        var c = document.getElementById("myCanvas");
        var ctx = c.getContext("2d");
        ctx.fillStyle = "lightgreen";
        ctx.shadowBlur = 0;
        ctx.fillRect(finishXY+2, finishXY+2, width-4, width-4);
    }

    function drawRunner(x, y){
        var c = document.getElementById("myCanvas");
        var ctx = c.getContext("2d");
        ctx.beginPath();
        ctx.arc(x, y, 25, 0, 2 * Math.PI);
        ctx.shadowBlur = 12;
        ctx.shadowColor = "black";
        ctx.fillStyle = "red";
        ctx.fill();
    }
    function eraseRunner(x, y){
        var c = document.getElementById("myCanvas");
        var ctx = c.getContext("2d");
        ctx.clearRect(x-runnerRadius*1.5, y-runnerRadius*1.5, runnerRadius*3, runnerRadius*3);
    }

    function checkDir(x, y, dir){
        var val = maze1[x][y];
        if(val>=8){                  //left
            if(dir=='L') return 0;
            val-=8;
        }
        if(val>=4){             //up
            if(dir=='U') return 0;
            val-=4;
        }
        if(val>=2){             //right
            if(dir=='R') return 0;
            val-=2;
        }
        if(val>=1){             //down
            if(dir=='D') return 0;
            val-=1;
        }
        return 1;
    }

    function displayMoves(){
        document.getElementById("moves").innerHTML = "Total moves: "+moves;
    }
    
    function moveRunner(dir, performer = 'input'){
        if (dir=='D') { // DOWN
            if (checkDir(indRow, indCol, dir)==1){
                eraseRunner(runnerX, runnerY);
                indRow+=1;
                runnerY+=width;
                drawRunner(runnerX, runnerY);
                moves+=1;
                if(performer=='input'){
                    undoStack.push(dir);
                    redoStack = [];
                } 
                else if(performer=="redo"){
                    undoStack.push(dir);
                }
                else if(performer=='undo'){
                    redoStack.push(dir);
                }
            }            
        }
        if (dir=='U') { // UP
            if (checkDir(indRow, indCol, dir)==1){
                eraseRunner(runnerX, runnerY);
                indRow-=1
                runnerY-=width;
                moves+=1;
                drawRunner(runnerX, runnerY);
                if(performer=='input'){
                    undoStack.push(dir);
                    redoStack = [];
                } 
                else if(performer=="redo"){
                    undoStack.push(dir);
                }
                else if(performer=='undo'){
                    redoStack.push(dir);
                }
            }
        }
        if (dir=='L') { // LEFT
            if (checkDir(indRow, indCol, 'L')==1){
                eraseRunner(runnerX, runnerY);
                indCol-=1;
                runnerX-=width;
                moves+=1;
                drawRunner(runnerX, runnerY);
                if(performer=='input'){
                    undoStack.push(dir);
                    redoStack = [];
                } 
                else if(performer=="redo"){
                    undoStack.push(dir);
                }
                else if(performer=='undo'){
                    redoStack.push(dir);
                }
            }
        }
        if (dir=='R') { // RIGHT
            if (checkDir(indRow, indCol, 'R')==1){
                eraseRunner(runnerX, runnerY);
                indCol+=1;
                runnerX+=width;
                moves+=1;
                drawRunner(runnerX, runnerY);
                if(performer=='input'){
                    undoStack.push(dir);
                    redoStack = [];
                } 
                else if(performer=="redo"){
                    undoStack.push(dir);
                }
                else if(performer=='undo'){
                    redoStack.push(dir);
                }
            }
        }
        displayMoves();
        if(indRow==N-1 && indCol==N-1){
            endGame("Congrats You Won!!!!");
        }
    }

    function createGraph(){
        for(var i = 0; i<N; i++){
            for (var j = 0; j<N; j++){
                if(checkDir(i, j, 'R')){
                    graph[N*i+j][N*i+j+1] = 1;
                    graph[N*i+j+1][N*i+j] = 1;
                }
                if(checkDir(i, j, 'D')){
                    graph[N*i+j][N*(i+1)+j] = 1;
                    graph[N*(i+1)+j][N*i+j] = 1; 
                }
            }
        }
    }

    function findSol(){
        path=[]
        var start=N*indRow+indCol
        var end=N*N-1;

        var i, j, k=0;
        var st = new Array(); //stack

        var visited = new Array(); 
        for(var p=0; p<100;p++){
            visited.push(0);
        }
        
        path.push(start);

        st.push(start);
        var top=0;

        visited[start] = 1;

        while(st.length>0 && path[k]!=end){
            i = st[top];
            for(j=0; j<N*N; j++){
                if(visited[j]==0 && graph[i][j]==1){
                    path.push(j);
                    k++;
                    visited[j]=1;
                    st.push(j);
                    top++;
                    break;
                }
            }
            if(j==N*N){
                path.pop();
                k--;
                st.pop();
                top--;
            }
        }
        console.log("path:"+path);
    }

    function drawSol(){
        var c = document.getElementById("myCanvas");
        var ctx = c.getContext("2d");
        ctx.fillStyle = "green";
        ctx.shadowBlur = 0;

        var ref = startPt+width/2
        var nextnode, x, y;

        for(var i=0; i<path.length; i++){
            nextnode = path[i];
            ctx.beginPath();
            x = ref+(nextnode%N)*width;
            y = ref+Math.floor(nextnode/N)*width;
            ctx.arc(x, y, 5, 0, 2 * Math.PI);
            ctx.fill();
        }
    }

    function clearSol(){
        var ref = startPt+width/2;
        var c = document.getElementById("myCanvas");
        var ctx = c.getContext("2d");
        var x=ref, y=ref;  //center of cells
        for(var i=0; i<N; i++){
            for(var j=0; j<N; j++){
                ctx.clearRect(x-runnerRadius*1.5, y-runnerRadius*1.5, runnerRadius*3, runnerRadius*3);
                x+=width;
            }
            x=ref;
            y+=width
        }
        drawRunner(runnerX, runnerY);
        drawFinish();
    }

    document.addEventListener('keydown', function(event) {
        if(work==1){
            if (event.keyCode === 40 || event.keyCode === 83) { // DOWN
                moveRunner('D');
            }
            else if (event.keyCode === 38 || event.keyCode === 87) { // UP
                moveRunner('U');
            }
            else if (event.keyCode === 37 || event.keyCode === 65) { // LEFT
                moveRunner('L');
            }
            else if (event.keyCode === 39 || event.keyCode === 68) { // RIGHT
                moveRunner('R');
            }
            else if (event.keyCode === 90) { // UNDO
                if(undoStack.length>0){
                    var dir = undoStack.pop();
                    if(dir=='D') moveRunner('U', "undo");
                    else if(dir=='U') moveRunner('D', "undo");
                    else if(dir=='L') moveRunner('R', "undo");
                    else if(dir=='R') moveRunner('L', "undo");
                }
            }
            else if (event.keyCode === 89) { // REDO
                if(redoStack.length>0){
                    var dir = redoStack.pop();
                    if(dir=='D') moveRunner('U', "redo");
                    else if(dir=='U') moveRunner('D', "redo");
                    else if(dir=='L') moveRunner('R', "redo");
                    else if(dir=='R') moveRunner('L', "redo");
                }
            }
            else if (event.keyCode === 80) { // REDO
                findSol();
                drawSol();
                setTimeout(function(){
                    clearSol();
                }, 2000);
            }
        }
    });
    });
    </script>

</body>
</html>
