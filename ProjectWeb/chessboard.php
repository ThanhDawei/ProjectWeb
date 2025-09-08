<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Chess</title>
<style>
  :root{--size:60px}
  body{
    margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
    background:#f3f4f6;font-family:system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial;
  }
  .container{display:flex;flex-direction:column;align-items:center;gap:12px}
  #board{
    display:grid;
    grid-template-columns:30px repeat(8,var(--size)) 30px;
    grid-template-rows:30px repeat(8,var(--size)) 30px;
    background:#fff;border:3px solid #333;
  }
  .cell{
    width:var(--size);height:var(--size);
    display:flex;align-items:center;justify-content:center;
    user-select:none; cursor:pointer;
  }
  .light{ background:#fdf0d9; }   /* kem */
  .dark{  background:#173d8f; }   /* xanh */
  .piece{
    font-size:40px;
  }
  .white-piece{ color:#fff; text-shadow:0 0 3px #000; }
  .black-piece{ color:#000; text-shadow:0 0 3px #fff; }

  .label{
    display:flex;align-items:center;justify-content:center;
    font-weight:700;color:#173d8f;background:#fff;font-size:16px;
    user-select:none;
  }
  .sel{ outline:4px solid #e11; }             /* ô được chọn */
  .can{ outline:4px solid #f59e0b }           /* nước đi hợp lệ */
  .check{ background:#ff6b6b !important; }    /* vua đang bị chiếu */
  #status{ 
    font-weight:700;color:#0f172a;text-align:center;
    min-height:2em;display:flex;align-items:center;justify-content:center;
  }
  .game-over{
    color:#dc2626;font-size:1.2em;
  }
  .checkmate{
    color:#dc2626;
  }
  .stalemate{
    color:#059669;
  }
  .reset-btn{
    margin-top:10px;padding:8px 16px;
    background:#3b82f6;color:white;border:none;border-radius:6px;
    cursor:pointer;font-weight:600;
  }
  .reset-btn:hover{
    background:#2563eb;
  }
  @media (max-width:560px){
    :root{--size:40px}
    .piece{font-size:28px}
  }
</style>
</head>
<body>
<div class="container">
  <div id="board"></div>
  <div id="status">Lượt: Trắng</div>
  <button class="reset-btn" onclick="resetGame()">Chơi lại</button>
</div>

<script>
/* ========= Dữ liệu & helper ========= */
const boardEl = document.getElementById('board');
const statusEl = document.getElementById('status');

let turn = 'w'; // 'w' = trắng, 'b' = đen
let gameOver = false;

// Bảng khởi tạo (hàng 8 -> hàng 1)
let initial = [
  ['r','n','b','q','k','b','n','r'], //8
  ['p','p','p','p','p','p','p','p'], //7
  ['.','.','.','.','.','.','.','.'], //6
  ['.','.','.','.','.','.','.','.'], //5
  ['.','.','.','.','.','.','.','.'], //4
  ['.','.','.','.','.','.','.','.'], //3
  ['P','P','P','P','P','P','P','P'], //2
  ['R','N','B','Q','K','B','N','R']  //1
];

// Unicode cho quân
const glyph = {
  'K':'♔','Q':'♕','R':'♖','B':'♗','N':'♘','P':'♙',
  'k':'♚','q':'♛','r':'♜','b':'♝','n':'♞','p':'♟',
  '.':''
};

const files = ['a','b','c','d','e','f','g','h'];
const ranks = [8,7,6,5,4,3,2,1];

let cells = []; // 8x8 DOM refs

function makeLabel(txt){
  const d = document.createElement('div');
  d.className = 'label';
  d.textContent = txt;
  return d;
}

function resetGame(){
  initial = [
    ['r','n','b','q','k','b','n','r'],
    ['p','p','p','p','p','p','p','p'],
    ['.','.','.','.','.','.','.','.'],
    ['.','.','.','.','.','.','.','.'],
    ['.','.','.','.','.','.','.','.'],
    ['.','.','.','.','.','.','.','.'],
    ['P','P','P','P','P','P','P','P'],
    ['R','N','B','Q','K','B','N','R']
  ];
  turn = 'w';
  gameOver = false;
  selected = null;
  legalMoves = [];
  render();
}

/* ========= Tìm vua ========= */
function findKing(color){
  const king = color === 'w' ? 'K' : 'k';
  for(let r = 0; r < 8; r++){
    for(let c = 0; c < 8; c++){
      if(initial[r][c] === king) return [r, c];
    }
  }
  return null;
}

/* ========= Kiểm tra ô có bị tấn công không ========= */
function isUnderAttack(r, c, byColor){
  for(let rr = 0; rr < 8; rr++){
    for(let cc = 0; cc < 8; cc++){
      const piece = initial[rr][cc];
      if(piece === '.') continue;
      const isWhite = piece === piece.toUpperCase();
      if((byColor === 'w' && !isWhite) || (byColor === 'b' && isWhite)) continue;
      
      const moves = getPieceMoves(rr, cc, false); // không kiểm tra check để tránh infinite recursion
      if(moves.some(([mr, mc]) => mr === r && mc === c)) return true;
    }
  }
  return false;
}

/* ========= Kiểm tra vua có đang bị chiếu không ========= */
function isInCheck(color){
  const kingPos = findKing(color);
  if(!kingPos) return false;
  const enemyColor = color === 'w' ? 'b' : 'w';
  return isUnderAttack(kingPos[0], kingPos[1], enemyColor);
}

/* ========= Kiểm tra nước đi có hợp lệ không (không để vua bị chiếu) ========= */
function isLegalMove(fromR, fromC, toR, toC){
  // Lưu trạng thái ban đầu
  const originalPiece = initial[toR][toC];
  const movingPiece = initial[fromR][fromC];
  
  // Thực hiện nước đi tạm thời
  initial[toR][toC] = movingPiece;
  initial[fromR][fromC] = '.';
  
  // Kiểm tra vua có bị chiếu không
  const color = movingPiece === movingPiece.toUpperCase() ? 'w' : 'b';
  const inCheck = isInCheck(color);
  
  // Khôi phục trạng thái
  initial[fromR][fromC] = movingPiece;
  initial[toR][toC] = originalPiece;
  
  return !inCheck;
}

/* ========= Luật di chuyển cơ bản ========= */
function inside(r,c){ return r>=0 && r<8 && c>=0 && c<8; }
function isOpposite(a,b){
  if(a==='.'||b==='.') return false;
  return (a===a.toUpperCase()) !== (b===b.toUpperCase());
}

function getPieceMoves(r, c, checkLegal = true){
  const piece = initial[r][c];
  if(piece==='.') return [];
  const isWhite = piece===piece.toUpperCase();
  const moves=[];
  const dir = isWhite?-1:1;

  function addOrStop(rr,cc){
    if(!inside(rr,cc)) return false;
    if(initial[rr][cc]==='.'){
      if(!checkLegal || isLegalMove(r, c, rr, cc)) moves.push([rr,cc]);
      return true;
    }
    if(isOpposite(piece,initial[rr][cc])){
      if(!checkLegal || isLegalMove(r, c, rr, cc)) moves.push([rr,cc]);
    }
    return false;
  }

  switch(piece.toUpperCase()){
    case 'P':
      if(inside(r+dir,c) && initial[r+dir][c]==='.'){
        if(!checkLegal || isLegalMove(r, c, r+dir, c)) moves.push([r+dir,c]);
        if((isWhite&&r===6)||(!isWhite&&r===1)){
          if(initial[r+2*dir][c]==='.' && (!checkLegal || isLegalMove(r, c, r+2*dir, c))) moves.push([r+2*dir,c]);
        }
      }
      [[dir,-1],[dir,1]].forEach(([dr,dc])=>{
        const rr=r+dr,cc=c+dc;
        if(inside(rr,cc)&&isOpposite(piece,initial[rr][cc]) && (!checkLegal || isLegalMove(r, c, rr, cc))) moves.push([rr,cc]);
      });
      break;
    case 'N':
      [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]].forEach(([dr,dc])=>{
        const rr=r+dr,cc=c+dc;
        if(inside(rr,cc)&&(initial[rr][cc]==='.'||isOpposite(piece,initial[rr][cc])) && (!checkLegal || isLegalMove(r, c, rr, cc))) moves.push([rr,cc]);
      });
      break;
    case 'B':
      [[-1,-1],[-1,1],[1,-1],[1,1]].forEach(([dr,dc])=>{
        let rr=r+dr,cc=c+dc;
        while(inside(rr,cc)){ if(!addOrStop(rr,cc)) break; rr+=dr; cc+=dc; }
      });
      break;
    case 'R':
      [[-1,0],[1,0],[0,-1],[0,1]].forEach(([dr,dc])=>{
        let rr=r+dr,cc=c+dc;
        while(inside(rr,cc)){ if(!addOrStop(rr,cc)) break; rr+=dr; cc+=dc; }
      });
      break;
    case 'Q':
      [[-1,0],[1,0],[0,-1],[0,1],[-1,-1],[-1,1],[1,-1],[1,1]].forEach(([dr,dc])=>{
        let rr=r+dr,cc=c+dc;
        while(inside(rr,cc)){ if(!addOrStop(rr,cc)) break; rr+=dr; cc+=dc; }
      });
      break;
    case 'K':
      [[-1,0],[1,0],[0,-1],[0,1],[-1,-1],[-1,1],[1,-1],[1,1]].forEach(([dr,dc])=>{
        const rr=r+dr,cc=c+dc;
        if(inside(rr,cc)&&(initial[rr][cc]==='.'||isOpposite(piece,initial[rr][cc])) && (!checkLegal || isLegalMove(r, c, rr, cc))) moves.push([rr,cc]);
      });
      break;
  }
  return moves;
}

function getMoves(r, c){
  return getPieceMoves(r, c, true);
}

/* ========= Tìm tất cả nước đi hợp lệ của một màn ========= */
function getAllLegalMoves(color){
  const moves = [];
  for(let r = 0; r < 8; r++){
    for(let c = 0; c < 8; c++){
      const piece = initial[r][c];
      if(piece === '.') continue;
      const isWhite = piece === piece.toUpperCase();
      if((color === 'w' && !isWhite) || (color === 'b' && isWhite)) continue;
      
      const pieceMoves = getMoves(r, c);
      pieceMoves.forEach(([toR, toC]) => {
        moves.push([r, c, toR, toC]);
      });
    }
  }
  return moves;
}

/* ========= Kiểm tra điều kiện kết thúc game ========= */
function checkGameStatus(){
  const legalMoves = getAllLegalMoves(turn);
  const inCheck = isInCheck(turn);
  
  if(legalMoves.length === 0){
    if(inCheck){
      // Chiếu bí
      gameOver = true;
      const winner = turn === 'w' ? 'Đen' : 'Trắng';
      return `checkmate:${winner} thắng! (Chiếu bí)`;
    } else {
      // Hòa cờ (pat)
      gameOver = true;
      return 'stalemate:Hòa cờ! (Không có nước đi hợp lệ)';
    }
  }
  
  if(inCheck){
    return `check:${turn === 'w' ? 'Trắng' : 'Đen'} đang bị chiếu!`;
  }
  
  return null;
}

function updateStatus(){
  if(gameOver) return;
  
  const status = checkGameStatus();
  if(status){
    const [type, message] = status.split(':');
    if(type === 'checkmate'){
      statusEl.innerHTML = `<span class="game-over checkmate">${message}</span>`;
    } else if(type === 'stalemate'){
      statusEl.innerHTML = `<span class="game-over stalemate">${message}</span>`;
    } else if(type === 'check'){
      statusEl.innerHTML = `<span class="checkmate">${message}</span>`;
    }
  } else {
    statusEl.textContent = 'Lượt: ' + (turn === 'w' ? 'Trắng' : 'Đen');
  }
}

/* ========= Render ========= */
let selected=null, legalMoves=[];
function render(){
  boardEl.innerHTML=''; cells=[];
  boardEl.appendChild(makeLabel(''));
  files.forEach(f=>boardEl.appendChild(makeLabel(f)));
  boardEl.appendChild(makeLabel(''));
  
  // Tìm vị trí vua đang bị chiếu
  let kingInCheck = null;
  if(isInCheck('w')){
    kingInCheck = findKing('w');
  } else if(isInCheck('b')){
    kingInCheck = findKing('b');
  }
  
  for(let r=0;r<8;r++){
    boardEl.appendChild(makeLabel(ranks[r]));
    const rowRefs=[];
    for(let c=0;c<8;c++){
      const div=document.createElement('div');
      div.className='cell '+((r+c)%2===0?'light':'dark');
      div.dataset.row=r; div.dataset.col=c;
      
      // Highlight vua đang bị chiếu
      if(kingInCheck && kingInCheck[0] === r && kingInCheck[1] === c){
        div.classList.add('check');
      }
      
      const piece=initial[r][c];
      if(piece!=='.'){
        const span=document.createElement('span');
        span.textContent=glyph[piece];
        span.className='piece '+(piece===piece.toUpperCase()?'white-piece':'black-piece');
        div.appendChild(span);
      }
      boardEl.appendChild(div);
      rowRefs.push(div);
    }
    cells.push(rowRefs);
    boardEl.appendChild(makeLabel(ranks[r]));
  }
  boardEl.appendChild(makeLabel(''));
  files.forEach(f=>boardEl.appendChild(makeLabel(f)));
  boardEl.appendChild(makeLabel(''));

  if(selected){
    const [sr,sc]=selected;
    cells[sr][sc].classList.add('sel');
    legalMoves.forEach(([rr,cc])=>cells[rr][cc].classList.add('can'));
  }
  updateStatus();
}

/* ========= Click handler ========= */
boardEl.addEventListener('click',ev=>{
  if(gameOver) return;
  
  const target=ev.target.closest('.cell');
  if(!target) return;
  const r=parseInt(target.dataset.row,10);
  const c=parseInt(target.dataset.col,10);
  if(Number.isNaN(r)||Number.isNaN(c)) return;
  const piece=initial[r][c];
  
  if(selected){
    if(selected[0]===r&&selected[1]===c){ 
      selected=null; legalMoves=[]; render(); return;
    }
    if(legalMoves.some(m=>m[0]===r&&m[1]===c)){
      initial[r][c]=initial[selected[0]][selected[1]];
      initial[selected[0]][selected[1]]='.';
      turn=(turn==='w')?'b':'w';
    }
    selected=null; legalMoves=[]; render(); return;
  }
  
  if(piece!=='.'){
    const isWhite=piece===piece.toUpperCase();
    if((isWhite&&turn==='w')||(!isWhite&&turn==='b')){
      selected=[r,c];
      legalMoves=getMoves(r,c);
      render();
    }
  }
});

render();
</script>
</body>
</html>