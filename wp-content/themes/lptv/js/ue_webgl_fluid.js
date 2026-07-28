import WebGLFluid from "./webgl_fluid.js"
class UeWebglFluid{constructor(options){this.canvas=options.canvas
this.eventsContainer=options.eventsContainer
this.fluidOptions=options.fluidOptions
if(this.fluidOptions.COLOR.startsWith("#000000")){this.fluidOptions.COLOR="#ffffff"
this.canvas.style.filter="invert(1)"}
this.init()}
init(){this.initFluid()}
initFluid(){WebGLFluid(this.canvas,{SIM_RESOLUTION:128,DYE_RESOLUTION:1024,DENSITY_DISSIPATION:3,VELOCITY_DISSIPATION:0.2,PRESSURE:0.8,PRESSURE_ITERATIONS:20,CURL:30,SPLAT_FORCE:6000,SHADING:true,COLORFUL:true,COLOR_UPDATE_SPEED:10,PAUSED:false,BACK_COLOR:{r:0,g:0,b:0},TRANSPARENT:true,BLOOM_ITERATIONS:8,BLOOM_RESOLUTION:128,BLOOM_INTENSITY:0.1,BLOOM_THRESHOLD:0.6,BLOOM_SOFT_KNEE:0.8,SUNRAYS_RESOLUTION:196,SUNRAYS_WEIGHT:1.0,CUSTOM_COLOR:false,COLOR:"#FD5A00",EVENTS_CONTAINER:this.eventsContainer,TEXTURE:null,...this.fluidOptions,})}}
export{UeWebglFluid}