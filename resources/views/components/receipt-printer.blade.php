@props([
    'merchant' => \App\Support\Settings::get('clinic_name', 'Clinic'),
    'order' => '',
    'date' => '',
    'items' => [],
    'subtotal' => 0,
    'tax' => 0,
    'taxLabel' => 'Tax',
    'total' => 0,
    'currency' => \App\Support\Settings::get('currency', 'TZS'),
    'extra' => [],
    'footer' => \App\Support\Settings::get('receipt_footer', 'Thank you, come again'),
])
@php
    $bars = [2,1,1,3,1,2,2,1,3,1,1,2,1,1,2,3,1,2,1,1,3,2,1,1,2,1,3,1,1,2,2,1,1,3,1,2];
    $uid = 'rp'.substr(md5($order.$date.microtime()), 0, 8);
@endphp
@once
<style>
.rp-card{position:relative;height:30rem;width:18rem;max-width:100%;user-select:none;overflow:hidden;border-radius:1.5rem;border:1px solid #e4e4e7;background:#fafafa;color:#18181b}
.rp-printer{position:absolute;inset-inline:0;top:0;height:4rem;background:#fff;border-bottom:1px solid #e4e4e7}
.rp-printer .rp-head{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem 0;font-size:.75rem}
.rp-printer .rp-merchant{font-weight:500}
.rp-printer .rp-status{color:#71717a}
.rp-slot{position:absolute;inset-inline:2rem;bottom:.5rem;height:.25rem;border-radius:9999px;background:#18181b}
.rp-paper{position:absolute;top:0;width:14rem;background:#fff;box-shadow:0 1px 2px rgb(0 0 0/.06),0 8px 24px -12px rgb(0 0 0/.25);left:50%;margin-left:-7rem;transform-origin:0 0;touch-action:none}
.rp-paper.is-hanging{cursor:grab}.rp-paper.is-hanging:active{cursor:grabbing}
.rp-paper.is-held{cursor:grabbing}
.rp-perf{position:absolute;inset-inline:.25rem;top:.25rem;border-top:2px dotted #d4d4d8}
.rp-body{padding:1.5rem 1.25rem 2rem;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;line-height:15px;color:#27272a}
.rp-center{text-align:center}.rp-muted{color:#71717a}
.rp-merchant-name{font-weight:600;font-size:.75rem;letter-spacing:.2em}
.rp-dash{margin:7px 0;overflow:hidden;white-space:nowrap;color:#a1a1aa}
.rp-row{display:flex;justify-content:space-between;gap:.75rem}
.rp-row .trunc{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rp-num{font-variant-numeric:tabular-nums}
.rp-total{font-weight:600;font-size:13px;margin-top:.25rem}
.rp-bars{margin-top:1rem;display:flex;height:2.25rem;justify-content:center;gap:1px}
.rp-bars i{display:block;background:#27272a}
.rp-btn{position:absolute;inset-inline:0;bottom:0;display:flex;justify-content:center;padding:1.25rem;transition:opacity .2s}
.rp-btn button{border:0;border-radius:9999px;background:#18181b;color:#fff;font-weight:500;font-size:.875rem;padding:.5rem 1rem;cursor:pointer}
.rp-btn button:hover{background:#3f3f46}
.rp-btn.rp-hidden{opacity:0;pointer-events:none}
</style>
@endonce

<div class="rp-card mx-auto" id="{{ $uid }}">
    <div class="rp-clip" style="position:absolute;inset-inline:0;bottom:0;top:64px;overflow:hidden">
        <div class="rp-paper" role="document">
            <span class="rp-perf"></span>
            <div class="rp-body">
                <p class="rp-center rp-merchant-name mb-0">{{ strtoupper($merchant) }}</p>
                <p class="rp-center rp-muted mb-0">{{ $date }}</p>
                <p class="rp-center rp-muted mb-0">Receipt {{ $order }}</p>
                <p class="rp-dash">{{ str_repeat('- ', 40) }}</p>
                @foreach ($items as $item)
                    <p class="rp-row mb-0">
                        <span class="trunc">{{ $item['qty'] }} × {{ $item['name'] }}</span>
                        <span class="rp-num">{{ number_format($item['qty'] * $item['price'], 2) }}</span>
                    </p>
                @endforeach
                <p class="rp-dash">{{ str_repeat('- ', 40) }}</p>
                <p class="rp-row rp-muted mb-0"><span>Subtotal</span><span class="rp-num">{{ number_format($subtotal, 2) }}</span></p>
                @if ($tax > 0)
                    <p class="rp-row rp-muted mb-0"><span>{{ $taxLabel }}</span><span class="rp-num">{{ number_format($tax, 2) }}</span></p>
                @endif
                @foreach ($extra as $label => $value)
                    <p class="rp-row rp-muted mb-0"><span>{{ $label }}</span><span class="rp-num">{{ $value }}</span></p>
                @endforeach
                <p class="rp-row rp-total mb-0"><span>Total</span><span class="rp-num">{{ $currency }} {{ number_format($total, 2) }}</span></p>
                <div class="rp-bars">@foreach ($bars as $i => $w)<i style="width:{{ $w }}px;{{ $i % 2 ? 'background:transparent' : '' }}"></i>@endforeach</div>
                <p class="rp-center rp-muted mt-3 mb-0">{{ $footer }}</p>
            </div>
        </div>
    </div>
    <div class="rp-printer">
        <div class="rp-head"><span class="rp-merchant">{{ $merchant }}</span><span class="rp-status">Ready</span></div>
        <div class="rp-slot"></div>
    </div>
    <div class="rp-btn rp-hidden"><button type="button" class="rp-print btn-arrow"><span class="btn-arrow-label">Print receipt</span><span class="btn-arrow-tile"><i class="hgi-stroke hgi-arrow-right-01"></i></span></button></div>
</div>

@once
<script>
(function(){
    const SLOT_Y=64, LINE=15, FEED_RATE=34, LINE_PAUSE=.055, LINE_JITTER=.05, STEP=1/120,
        GRAVITY=2600, DAMPING=.996, MAX_PULL=18, TEAR_DRAG=72, TEAR_SPEED=900, GLIDE=.35, REST_EPS=.02,
        TOOTH=8,
        edge=s=>`conic-gradient(from ${s==='bottom'?'-45deg at 50% 100%':'135deg at 50% 0'}, #0000, #000 1deg 89deg, #0000 90deg) 50% ${s==='bottom'?'100%':'0'} / ${TOOTH}px ${TOOTH/2}px repeat-x`,
        FILL=`linear-gradient(#000 0 0) 0 ${TOOTH/2}px / 100% calc(100% - ${TOOTH}px) no-repeat`,
        MASK_REST=`${edge('bottom')}, linear-gradient(#000 0 0) 0 0 / 100% calc(100% - ${TOOTH/2}px) no-repeat`,
        MASK_TORN=`${edge('top')}, ${edge('bottom')}, ${FILL}`;
    const turn=(px,py,a)=>{const c=Math.cos(a),s=Math.sin(a);return[px*c-py*s,px*s+py*c]};
    const damp=(d,m)=>m*(1-Math.exp(-d/m));

    function Printer(card){
        this.card=card;
        this.paper=card.querySelector('.rp-paper');
        this.clip=card.querySelector('.rp-clip');
        this.printerEl=card.querySelector('.rp-printer');
        this.statusEl=card.querySelector('.rp-status');
        this.btnWrap=card.querySelector('.rp-btn');
        this.torn=false;
        this.s={phase:'idle',width:0,height:0,slotX:0,cx:0,cy:0,pcx:0,pcy:0,angle:0,spin:0,
            hx:0,hy:0,hingeX:0,hingeY:0,hidden:0,feedTarget:0,pause:0,pointerId:-1,
            grabX:0,grabY:0,startX:0,startY:0,samples:[],raf:0,last:0,carry:0,visible:false,
            reduceMotion:window.matchMedia('(prefers-reduced-motion: reduce)').matches};
        this.bind();
    }
    Printer.prototype={
        bind(){
            const s=this.s;
            this.paper.addEventListener('pointerdown',e=>this.onDown(e));
            this.paper.addEventListener('pointermove',e=>this.onMove(e));
            this.paper.addEventListener('pointerup',e=>this.onUp(e));
            this.paper.addEventListener('pointercancel',e=>this.onUp(e));
            this.paper.addEventListener('lostpointercapture',e=>this.onUp(e));
            this.card.querySelector('.rp-print').addEventListener('click',()=>this.print());
            this.measure(); this.placeAtSlot(-s.height); this.draw();
            let printed=false,onScreen=false;
            const update=()=>{
                s.visible=onScreen&&!document.hidden;
                if(!s.visible){cancelAnimationFrame(s.raf);s.raf=0;s.last=0;return}
                if(!printed){printed=true;this.print()}
                this.wake();
            };
            const io=new IntersectionObserver(([en])=>{onScreen=en.isIntersecting;update()},{threshold:.4});
            io.observe(this.card);
            document.addEventListener('visibilitychange',update);
        },
        measure(){const s=this.s;s.width=this.paper.offsetWidth;s.height=this.paper.offsetHeight;s.slotX=this.card.clientWidth/2},
        setPhase(p){
            this.s.phase=p;
            this.paper.classList.toggle('is-hanging',p==='hanging');
            this.paper.classList.toggle('is-held',p==='held');
            this.statusEl.textContent=p==='printing'?'Printing…':p==='hanging'?'Pull down to tear':'Ready';
            this.btnWrap.classList.toggle('rp-hidden',p!=='idle');
            if(p==='idle')this.card.querySelector('.rp-print .btn-arrow-label').textContent=this.torn?'Print another':'Print receipt';
        },
        draw(){
            const s=this.s,[ox,oy]=turn(s.width/2,s.height/2,s.angle),left=s.slotX-s.width/2;
            this.paper.style.transform=`translate3d(${(s.cx-ox-left).toFixed(2)}px,${(s.cy-oy-SLOT_Y).toFixed(2)}px,0) rotate(${s.angle.toFixed(4)}rad)`;
        },
        hangAt(a){const s=this.s,[dx,dy]=turn(s.width/2-s.hingeX,s.height/2-s.hingeY,a);return[s.hx+dx,s.hy+dy]},
        placeAtSlot(pull){
            const s=this.s;s.hingeX=s.width/2;s.hingeY=0;s.hx=s.slotX;s.hy=SLOT_Y+pull;
            const[cx,cy]=this.hangAt(0);s.cx=cx;s.cy=cy;s.pcx=cx;s.pcy=cy;s.angle=0;
        },
        stepPrinting(dt){
            const s=this.s;
            if(s.pause>0)s.pause-=dt;
            else if(s.feedTarget>0){s.feedTarget=Math.max(0,s.feedTarget-LINE);s.pause=LINE_PAUSE+Math.random()*LINE_JITTER}
            s.hidden+=(s.feedTarget-s.hidden)*Math.min(1,FEED_RATE*dt);
            if(Math.abs(s.hidden-s.feedTarget)<.05)s.hidden=s.feedTarget;
            this.placeAtSlot(-s.hidden);
            this.printerEl.style.transform=s.pause>0?'':`translate3d(${(Math.sin(performance.now()/9)*.4).toFixed(2)}px,0,0)`;
            if(s.hidden===0&&s.feedTarget===0){this.printerEl.style.transform='';this.setPhase('hanging');return 0}
            return 1;
        },
        stepPendulum(){
            const s=this.s;
            const vx=(s.cx-s.pcx)*DAMPING,vy=(s.cy-s.pcy)*DAMPING;
            s.pcx=s.cx;s.pcy=s.cy;s.cx+=vx;s.cy+=vy+GRAVITY*STEP*STEP;
            const rod=Math.hypot(s.width/2-s.hingeX,s.height/2-s.hingeY),dx=s.cx-s.hx,dy=s.cy-s.hy,dist=Math.hypot(dx,dy)||1;
            s.cx=s.hx+dx/dist*rod;s.cy=s.hy+dy/dist*rod;
            const rest=Math.atan2(s.height/2-s.hingeY,s.width/2-s.hingeX);
            s.angle=Math.atan2(s.cy-s.hy,s.cx-s.hx)-rest;
            return Math.abs(vx)+Math.abs(vy);
        },
        stepFalling(){
            const s=this.s;
            let vx=(s.cx-s.pcx)*DAMPING;const vy=(s.cy-s.pcy)*DAMPING;
            vx+=Math.sin(s.angle)*Math.max(vy,0)*GLIDE*STEP*10;
            s.pcx=s.cx;s.pcy=s.cy;s.cx+=vx;s.cy+=vy+GRAVITY*STEP*STEP;
            s.angle+=s.spin*STEP;s.spin*=.995;
            return s.cy>this.card.clientHeight+Math.hypot(s.width,s.height)/2?0:1;
        },
        stepPhysics(el){
            const s=this.s;let busy=0;s.carry+=el;
            while(s.carry>=STEP){s.carry-=STEP;
                if(s.phase==='falling')busy=this.stepFalling();
                else if(s.phase==='hanging'||s.phase==='held')busy=this.stepPendulum();
            }
            return busy;
        },
        tick(now){
            const s=this.s;s.raf=0;
            const el=s.last?Math.min((now-s.last)/1000,.05):STEP;s.last=now;
            const busy=s.phase==='printing'?this.stepPrinting(el):this.stepPhysics(el);
            this.draw();
            if(s.phase==='falling'&&busy===0){this.setPhase('idle');s.last=0;return}
            if((s.phase==='printing'||s.phase==='falling'||s.pointerId>=0||busy>REST_EPS)&&s.visible)s.raf=requestAnimationFrame(n=>this.tick(n));
            else s.last=0;
        },
        wake(){const s=this.s;if(!s.raf&&s.visible)s.raf=requestAnimationFrame(n=>this.tick(n))},
        print(){
            const s=this.s;
            if(s.phase!=='idle'&&!this.torn)return;
            this.measure();this.torn=false;this.clip.style.overflow='hidden';
            this.paper.style.visibility='';this.paper.style.mask=this.paper.style.webkitMask=MASK_REST;
            if(!this.paper.querySelector('.rp-perf'))this.paper.insertAdjacentHTML('afterbegin','<span class="rp-perf"></span>');
            if(s.reduceMotion){this.placeAtSlot(0);this.draw();this.setPhase('hanging');return}
            s.hidden=s.height;s.feedTarget=s.height;s.pause=.25;
            this.placeAtSlot(-s.hidden);this.draw();this.setPhase('printing');this.wake();
        },
        tear(held){
            const s=this.s;this.torn=true;this.clip.style.overflow='visible';
            this.paper.style.mask=this.paper.style.webkitMask=MASK_TORN;
            this.paper.querySelector('.rp-perf')?.remove();
            if(navigator.vibrate)navigator.vibrate(14);
            this.printerEl.animate([{transform:'translate3d(0,1.5px,0)'},{transform:'translate3d(0,0,0)'}],{duration:220,easing:'cubic-bezier(0.23,1,0.32,1)'});
            if(s.reduceMotion){this.setPhase('idle');return}
            this.setPhase(held?'held':'falling');this.wake();
        },
        localPoint(e){const r=this.card.getBoundingClientRect();return{x:e.clientX-r.left,y:e.clientY-r.top}},
        toSheet(x,y){
            const s=this.s,[ox,oy]=turn(s.width/2,s.height/2,s.angle);
            return turn(x-(s.cx-ox),y-(s.cy-oy),-s.angle);
        },
        onDown(e){
            const s=this.s;
            if(s.phase!=='hanging'||(e.pointerType==='mouse'&&e.button!==0))return;
            const{x,y}=this.localPoint(e);
            this.paper.setPointerCapture(e.pointerId);
            s.pointerId=e.pointerId;s.startX=x;s.startY=y;
            const[gx,gy]=this.toSheet(x,y);s.grabX=gx;s.grabY=gy;
            s.samples=[{t:e.timeStamp,x,y}];this.wake();
        },
        dragAttached(x,y){
            const s=this.s,pull=damp(Math.max(0,y-s.startY),MAX_PULL);
            s.hingeX=s.width/2;s.hingeY=0;s.hx=s.slotX;s.hy=SLOT_Y+pull;
            const reach=Math.max(s.grabY,24),angle=-Math.atan2(x-s.slotX-(s.grabX-s.width/2),reach);
            const[cx,cy]=this.hangAt(angle*.7);
            s.pcx=s.cx;s.pcy=s.cy;s.cx=cx;s.cy=cy;s.angle=angle*.7;
        },
        onMove(e){
            const s=this.s;
            if(s.pointerId!==e.pointerId)return;
            const{x,y}=this.localPoint(e);
            s.samples.push({t:e.timeStamp,x,y});if(s.samples.length>5)s.samples.shift();
            if(s.phase==='hanging'){
                if(y-s.startY>TEAR_DRAG){s.hingeX=s.grabX;s.hingeY=s.grabY;s.hx=x;s.hy=y;this.tear(true)}
                else this.dragAttached(x,y);
            }else if(s.phase==='held'){s.hx=x;s.hy=y}
            this.wake();
        },
        releaseVelocity(){
            const sm=this.s.samples,f=sm[0],l=sm[sm.length-1]||f,dt=Math.max((l.t-f.t)/1000,.016);
            return{vx:(l.x-f.x)/dt,vy:(l.y-f.y)/dt};
        },
        onUp(e){
            const s=this.s;
            if(s.pointerId!==e.pointerId)return;
            s.pointerId=-1;
            const{vx,vy}=this.releaseVelocity();
            if(s.phase==='held'){
                const pa=s.angle;this.stepPendulum();
                s.spin=(s.angle-pa)/STEP+vx*.002;
                s.pcx=s.cx-vx*STEP*.6;s.pcy=s.cy-vy*STEP*.6;
                this.setPhase('falling');
            }else if(s.phase==='hanging'&&vy>TEAR_SPEED){
                s.pcx=s.cx-vx*STEP*.5;s.pcy=s.cy-vy*STEP*.5;s.spin=vx*.004;this.tear(false);
            }else if(s.phase==='hanging'){
                s.hy=SLOT_Y;s.pcx=s.cx;s.pcy=s.cy;
            }
            this.wake();
        }
    };
    document.querySelectorAll('.rp-card').forEach(c=>{if(!c._rp)c._rp=new Printer(c)});
})();
</script>
@endonce
