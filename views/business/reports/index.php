<?php
$pageTitle = 'Reports — ' . e($book['name']);
ob_start();

$prevMonth  = date('Y-m', strtotime($month . '-01 -1 month'));
$nextMonth  = date('Y-m', strtotime($month . '-01 +1 month'));
$isCurrent  = $month === date('Y-m');

$catColors = [
    'invoice_sale'            => ['var(--green)',  'fa-file-invoice',       'Sale Invoice'],
    'invoice_purchase'        => ['var(--blue)',   'fa-cart-shopping',      'Purchase Invoice'],
    'sales_return'            => ['var(--red)',    'fa-rotate-left',        'Sales Return (Refund)'],
    'purchase_return'         => ['var(--green)',  'fa-truck-ramp-box',     'Purchase Return'],
    'return_discount_kept'    => ['var(--green)',  'fa-piggy-bank',         'Return Discount Kept'],
    'return_loss'             => ['var(--red)',    'fa-triangle-exclamation','Return Loss'],
    'delivery_expense'        => ['var(--amber)',  'fa-truck',              'Delivery Expense'],
    'Expense: General'        => ['var(--red)',    'fa-receipt',            'Expense'],
    'Fund Received'           => ['var(--green)',  'fa-piggy-bank',         'Fund In'],
    'Fund Withdrawn'          => ['var(--red)',    'fa-piggy-bank',         'Fund Out'],
];
function catStyle(string $cat): array {
    global $catColors;
    foreach ($catColors as $k => $v) {
        if (str_contains($cat, $k)) return $v;
    }
    return ['var(--text-muted)', 'fa-circle', $cat];
}
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Reports</span>
        </div>
        <h1><i class="fa-solid fa-chart-line" style="color:var(--brand)"></i> Reports & Statements</h1>
        <p style="color:var(--text-muted);font-size:13px">All incoming and outgoing transactions in one place. Click any row to view details.</p>
    </div>
</div>

<!-- Summary bar -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px">
    <div class="card" style="text-align:center;border-top:3px solid var(--green)">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Total Income</div>
        <div style="font-size:24px;font-weight:800;color:var(--green);margin-top:4px"><?= $sym.number_format($totalIn,0) ?></div>
    </div>
    <div class="card" style="text-align:center;border-top:3px solid var(--red)">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Total Outgoing</div>
        <div style="font-size:24px;font-weight:800;color:var(--red);margin-top:4px"><?= $sym.number_format($totalOut,0) ?></div>
    </div>
    <div class="card" style="text-align:center;border-top:3px solid var(--brand)">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Net Balance</div>
        <?php $net = $totalIn - $totalOut; ?>
        <div style="font-size:24px;font-weight:800;color:<?= $net>=0?'var(--green)':'var(--red)' ?>;margin-top:4px">
            <?= ($net >= 0 ? '+' : '') . $sym.number_format($net,0) ?>
        </div>
    </div>
</div>

<!-- Controls -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <a href="?month=<?= $prevMonth ?>&type=<?= $typeFilter ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <span style="font-weight:600;font-size:14px;min-width:120px;text-align:center">
        <?= date('F Y', strtotime($month.'-01')) ?>
    </span>
    <?php if (!$isCurrent): ?>
    <a href="?month=<?= $nextMonth ?>&type=<?= $typeFilter ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-chevron-right"></i>
    </a>
    <?php else: ?>
    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed">
        <i class="fa-solid fa-chevron-right"></i>
    </span>
    <?php endif; ?>

    <div style="display:flex;gap:6px;margin-left:auto;flex-wrap:wrap">
        <?php foreach (['all'=>'All','in'=>'Income Only','out'=>'Outgoing Only'] as $k=>$lbl): ?>
        <a href="?month=<?= $month ?>&type=<?= $k ?>"
           class="btn btn-sm <?= $typeFilter===$k ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $lbl ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Ledger table -->
<div class="card" style="padding:0;overflow:hidden">
    <?php if (empty($entries)): ?>
    <div style="padding:48px;text-align:center;color:var(--text-muted)">
        <i class="fa-solid fa-chart-line" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px"></i>
        No transactions found for this period.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="background:var(--bg)">
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Date</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Category</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Ref / Party</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:right;border-bottom:1px solid var(--border)">In</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:right;border-bottom:1px solid var(--border)">Out</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $e):
            [$clr,$icon,$catLabel] = catStyle($e['category']);
            $isIn   = $e['direction'] === 'in';
            $amount = (float)$e['amount'];
        ?>
        <tr style="border-bottom:1px solid var(--border);cursor:pointer"
            onclick="window.location='<?= e($e['href'] ?? '#') ?>'"
            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
            <td style="padding:10px 14px;font-size:12px;color:var(--text-muted);white-space:nowrap">
                <?= date('d M', strtotime($e['date'])) ?>
            </td>
            <td style="padding:10px 14px">
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:<?= $clr ?>">
                    <i class="fa-solid <?= $icon ?>"></i>
                    <?= e($catLabel) ?>
                </span>
            </td>
            <td style="padding:10px 14px;font-size:13px">
                <div style="font-weight:500"><?= e($e['invoice_no'] ?? '—') ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= e($e['party'] ?? '') ?></div>
            </td>
            <td style="padding:10px 14px;text-align:right;font-weight:700;font-size:14px;color:var(--green)">
                <?= $isIn ? $sym.number_format($amount,0) : '' ?>
            </td>
            <td style="padding:10px 14px;text-align:right;font-weight:700;font-size:14px;color:var(--red)">
                <?= !$isIn ? $sym.number_format($amount,0) : '' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:var(--bg);border-top:2px solid var(--border)">
                <td colspan="3" style="padding:10px 14px;font-weight:700;font-size:13px">TOTAL</td>
                <td style="padding:10px 14px;text-align:right;font-weight:800;font-size:15px;color:var(--green)"><?= $sym.number_format($totalIn,0) ?></td>
                <td style="padding:10px 14px;text-align:right;font-weight:800;font-size:15px;color:var(--red)"><?= $sym.number_format($totalOut,0) ?></td>
            </tr>
        </tfoot>
    </table>
    </div>
    <?php endif; ?>
</div>


<script>
(function(){
var allRows=[],filterF='all',searchQ='',sortKey='date-desc',perPage=20,curPage=1;
function init(){
    allRows=Array.from(document.querySelectorAll('#repTable tbody tr'));
    var si=document.getElementById('repTableSearch'),sc=document.getElementById('repTableClear');
    if(si){si.addEventListener('input',function(){searchQ=this.value.toLowerCase().trim();sc.classList.toggle('visible',searchQ.length>0);curPage=1;render();});sc.addEventListener('click',function(){si.value='';searchQ='';sc.classList.remove('visible');curPage=1;render();});}
    var ss=document.getElementById('repTableSort');if(ss)ss.addEventListener('change',function(){sortKey=this.value;curPage=1;render();});
    document.querySelectorAll('[data-lmf]').forEach(function(b){b.addEventListener('click',function(){filterF=this.getAttribute('data-lmf');document.querySelectorAll('[data-lmf]').forEach(function(x){x.classList.remove('btn-primary');x.classList.add('btn-secondary');});this.classList.add('btn-primary');this.classList.remove('btn-secondary');curPage=1;render();});}); 
    render();
}
function parseD(r){var v=r.getAttribute('data-date');return v?new Date(v):new Date(0);}
function getAmt(r){for(var i=2;i<8;i++){var c=r.querySelectorAll('td')[i];if(c){var n=parseFloat(c.textContent.replace(/[^0-9.]/g,''));if(!isNaN(n)&&n>0)return n;}}return 0;}
function render(){
    var f=allRows.filter(function(row){
        if(filterF!=='all'){var rf=row.getAttribute('data-filter')||'';if(rf.split(',').map(function(s){return s.trim();}).indexOf(filterF)===-1)return false;}
        if(searchQ&&row.textContent.toLowerCase().indexOf(searchQ)===-1)return false;
        return true;
    });
    f.sort(function(a,b){
        var k=sortKey;
        if(k==='date-desc')return parseD(b)-parseD(a);if(k==='date-asc')return parseD(a)-parseD(b);
        if(k==='amt-desc')return getAmt(b)-getAmt(a);if(k==='amt-asc')return getAmt(a)-getAmt(b);
        var ta=a.textContent.trim(),tb=b.textContent.trim();
        if(k==='az')return ta.localeCompare(tb);if(k==='za')return tb.localeCompare(ta);
        return 0;
    });
    var pp=perPage==='all'?Infinity:parseInt(perPage),total=f.length,tpg=pp===Infinity?1:Math.max(1,Math.ceil(total/pp));
    if(curPage>tpg)curPage=tpg;if(curPage<1)curPage=1;
    var s=pp===Infinity?0:(curPage-1)*pp,e2=pp===Infinity?total:Math.min(s+pp,total);
    var tbody=document.querySelector('#repTable tbody'),colC=(document.querySelector('#repTable thead tr')||{}).children.length||6;
    while(tbody.firstChild)tbody.removeChild(tbody.firstChild);
    if(f.length===0){var nr=document.createElement('tr');nr.className='lm-no-results';var nd=document.createElement('td');nd.setAttribute('colspan',colC);nd.textContent='No records match.';nr.appendChild(nd);tbody.appendChild(nr);}
    else{var lastM=null;f.slice(s,e2).forEach(function(row){
        var d=parseD(row);
        if(d.getTime()>0){var mk=d.getFullYear()+'-'+d.getMonth();if(mk!==lastM){lastM=mk;var sep=document.createElement('tr');sep.className='month-sep';var std=document.createElement('td');std.setAttribute('colspan',colC);std.textContent=d.toLocaleDateString('en-GB',{month:'long',year:'numeric'});sep.appendChild(std);tbody.appendChild(sep);}}
        tbody.appendChild(row);
    });}
    renderPager(document.getElementById('repPager'),total,tpg,s,e2,pp);
}
function renderPager(el,total,tpg,s,e2,pp){if(!el)return;el.innerHTML='';var wrap=document.createElement('div');wrap.className='lm-pagination';var info=document.createElement('div');info.className='lm-page-info';info.textContent=total===0?'No results':pp===Infinity?'All '+total+' records':'Showing '+(s+1)+'–'+e2+' of '+total;wrap.appendChild(info);if(tpg>1){var pages=document.createElement('div');pages.className='lm-pages';function mkB(l,pg){var b=document.createElement('button');b.className='lm-page-btn';if(pg===curPage)b.classList.add('active');b.textContent=l;if(pg)b.addEventListener('click',function(){curPage=pg;render();});return b;}if(curPage>1)pages.appendChild(mkB('‹',curPage-1));var ns=[];if(tpg<=7){for(var i=1;i<=tpg;i++)ns.push(i);}else{ns=[1];if(curPage>3)ns.push('…');for(var i=Math.max(2,curPage-1);i<=Math.min(tpg-1,curPage+1);i++)ns.push(i);if(curPage<tpg-2)ns.push('…');ns.push(tpg);}ns.forEach(function(p){var b=mkB(p,p==='…'?0:p);if(p==='…')b.classList.add('lm-ellipsis');pages.appendChild(b);});if(curPage<tpg)pages.appendChild(mkB('›',curPage+1));wrap.appendChild(pages);}var ppW=document.createElement('div');ppW.className='lm-per-page-wrap';var sl=document.createElement('select');sl.className='lm-select';sl.style.padding='4px 8px';sl.style.margin='0 4px';[20,50,100,'all'].forEach(function(v){var o=document.createElement('option');o.value=v;o.textContent=v==='all'?'All':v;if((pp===Infinity&&v==='all')||pp===v)o.selected=true;sl.appendChild(o);});sl.addEventListener('change',function(){perPage=sl.value;curPage=1;render();});ppW.appendChild(document.createTextNode('Show '));ppW.appendChild(sl);ppW.appendChild(document.createTextNode(' per page'));wrap.appendChild(ppW);el.appendChild(wrap);}
document.addEventListener('DOMContentLoaded',init);
})();
</script>
<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>
