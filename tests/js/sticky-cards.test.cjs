const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../src/blocks/sticky-cards/view.js'), 'utf8');
function setup({matches=true,height=900,cardHeight=200,count=3}={}) {
 const events={},listeners={},classes=new Set();
 const preference={matches,addEventListener:(name,fn)=>listeners[name]=fn};
 const cards=Array.from({length:count},()=>({offsetHeight:cardHeight,classList:{contains:name=>name==='wp-block-group'},style:{setProperty(){}}}));
 const stack={children:cards,ownerDocument:{activeElement:null},contains:el=>!!el,classList:{toggle:(name,on)=>on?classes.add(name):classes.delete(name),remove:name=>classes.delete(name)},addEventListener:(name,fn)=>events[name]=fn};
 const window={innerHeight:height,matchMedia:()=>preference,addEventListener:(name,fn)=>listeners[name]=fn};
 vm.runInNewContext(source,{window,document:{querySelectorAll:()=>[stack]},getComputedStyle:()=>({getPropertyValue:name=>name==='--gb-card-offset'?'96':'24'}),cancelAnimationFrame(){},requestAnimationFrame:fn=>{fn();return 1;}});
 return {ready:()=>classes.has('gb-stack-ready'),events,stack,cards,window,preference,listeners};
}
test('enhances only a multi-card stack whose complete content fits the viewport',()=>{
 assert.equal(setup().ready(),true);
 assert.equal(setup({cardHeight:850}).ready(),false);
 assert.equal(setup({count:1}).ready(),false);
 assert.equal(setup({matches:false}).ready(),false);
});
test('keyboard focus restores document flow and never re-enables while focus is inside',()=>{
 const s=setup();s.stack.ownerDocument.activeElement={};s.events.focusin();assert.equal(s.ready(),false);s.listeners.resize();assert.equal(s.ready(),false);s.stack.ownerDocument.activeElement=null;s.events.focusout();assert.equal(s.ready(),true);
});
test('responds to viewport and reduced-motion changes without hiding content',()=>{
 const s=setup();s.window.innerHeight=250;s.listeners.resize();assert.equal(s.ready(),false);s.window.innerHeight=900;s.listeners.resize();assert.equal(s.ready(),true);s.preference.matches=false;s.listeners.change();assert.equal(s.ready(),false);
});
