var Gr="https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js",$t="data-fp-hls-loader",Ee=null;function ht(){return typeof window>"u"||window.Hls?Promise.resolve():Ee||(Ee=new Promise((t,i)=>{let r=o=>{o.addEventListener("load",()=>t(),{once:!0}),o.addEventListener("error",()=>{Ee=null,i(new Error("Failed to load hls.js from CDN"))},{once:!0})},a=document.querySelector(`script[${$t}]`);if(a){if(window.Hls){t();return}r(a);return}let s=document.createElement("script");s.src=Gr,s.async=!0,s.crossOrigin="anonymous",s.setAttribute($t,"1"),r(s),(document.head??document.documentElement).appendChild(s)}),Ee)}function ae(){let t=window.Hls;if(!t)throw new Error("Hls is not available; call loadHlsFromCdn() first");return t}var Te=class{removeEventListener(t,i,r){}addEventListener(t,i,r){}dispatchEvent(t){return!0}};function Zt(){return class extends Te{}}function Qr(){return class extends Te{}}var Yr={get(e){},define(e,t,i){},upgrade(e){},getName(e){throw new Error("Function not implemented.")},whenDefined(e){throw new Error("Function not implemented.")}};function Xr(){return class{constructor(e,t={}){this.eventDetail=t?.detail}get detail(){return this.eventDetail}}}function Jr(e,t){return new(Zt())}function xr(){let e=Qr();return{document:{createElement:Jr},DocumentFragment:e,customElements:Yr,CustomEvent:Xr(),EventHandler:Te,HTMLElement:Zt()}}var Kt=typeof window>"u"||typeof globalThis.customElements>"u",ft=Kt?xr():globalThis,Oe=ft,m=Kt?ft.document:globalThis.document,Ro=ft.CustomEvent;function j(e){if(!e.initialPlayClick)return;e.progressBarContainer.querySelectorAll(".chapter-marker, .chapter-marker-end").forEach(s=>s.remove());let i=e.progressBar.getBoundingClientRect().width,r=e.video.offsetWidth,a;r<170||r>=171&&r<=500?a="chapter-marker-mini":r>=471&&r<=950?a="chapter-marker-md":a="chapter-marker-lg",e.chapters.forEach(s=>{let o=m.createElement("div");o.className=`chapter-marker ${a}`;let l=s.startTime/e.video.duration*i;if(o.style.left=`${l+20}px`,e.progressBarContainer.appendChild(o),s.endTime!==void 0){let u=m.createElement("div");u.className=`chapter-marker-end ${a}`;let n=s.endTime/e.video.duration*i;u.style.left=`${n+20}px`,e.progressBarContainer.appendChild(u)}})}function yt(e){let t=e.video.currentTime,i=e.chapters.find(a=>t>=a.startTime&&t<(a.endTime??1/0)),r=i?{startTime:i.startTime,endTime:i.endTime,value:i.value}:null;return(!e.previousChapter&&r||e.previousChapter&&r&&(e.previousChapter.startTime!==r.startTime||e.previousChapter.endTime!==r.endTime||e.previousChapter.value!==r.value))&&(e.previousChapter=r,e.dispatchEvent(new Event("chapterchange"))),r}function Gt(e){e.chapters.length>0?(e.thumbnail.classList.add("chapters"),e.thumbnail.appendChild(e.chapterDisplay)):e.thumbnail.classList.remove("chapters")}var ee=`<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M3 9.00047V15.0005H7L12 20.0005V4.00047L7 9.00047H3ZM10 8.83047V15.1705L7.83 13.0005H5V11.0005H7.83L10 8.83047ZM16.5 12.0005C16.5 10.2305 15.48 8.71047 14 7.97047V16.0205C15.48 15.2905 16.5 13.7705 16.5 12.0005ZM14 3.23047V5.29047C16.89 6.15047 19 8.83047 19 12.0005C19 15.1705 16.89 17.8505 14 18.7105V20.7705C18.01 19.8605 21 16.2805 21 12.0005C21 7.72047 18.01 4.14047 14 3.23047Z" fill="currentColor"/>
  </svg>`,se=`<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M4.33999 2.93457L2.92999 4.34457L7.28999 8.70457L6.99999 9.00457H2.99999V15.0046H6.99999L12 20.0046V13.4146L16.18 17.5946C15.53 18.0846 14.8 18.4746 14 18.7046V20.7646C15.34 20.4646 16.57 19.8446 17.61 19.0146L19.66 21.0646L21.07 19.6546L4.33999 2.93457ZM9.99999 15.1746L7.82999 13.0046H4.99999V11.0046H7.82999L8.70999 10.1246L9.99999 11.4146V15.1746ZM19 12.0046C19 12.8246 18.85 13.6146 18.59 14.3446L20.12 15.8746C20.68 14.7046 21 13.3946 21 12.0046C21 7.72457 18.01 4.14457 14 3.23457V5.29457C16.89 6.15457 19 8.83457 19 12.0046ZM12 4.00457L10.12 5.88457L12 7.76457V4.00457ZM16.5 12.0046C16.5 10.2346 15.48 8.71457 14 7.97457V9.76457L16.48 12.2446C16.49 12.1646 16.5 12.0846 16.5 12.0046Z" fill="currentColor"/>
    </svg>`,Qt=`<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M16.25 7.97V16.02C17.73 15.29 18.75 13.77 18.75 12C18.75 10.23 17.73 8.71 16.25 7.97ZM5.25 9V15H9.25L14.25 20V4L9.25 9H5.25ZM12.25 8.83V15.17L10.08 13H7.25V11H10.08L12.25 8.83Z" fill="currentColor"/>
</svg>`;var bt=`<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 32 32" fill="none">
<path d="M5 14.0006V16.0006C9.97002 16.0006 14 20.0306 14 25.0006H16C16 18.9256 11.075 14.0006 5 14.0006Z" fill="currentColor"/>
<path d="M5 18.0006V20.0006C7.76 20.0006 10 22.2406 10 25.0006H12C12 21.1356 8.86499 18.0006 5 18.0006ZM5 22.0006V25.0006H8C8 23.3456 6.65502 22.0006 5 22.0006ZM25 7.00061H7.00002C5.89499 7.00061 5 7.8956 5 9.00058V12.0006H7.00002V9.00058H25V23.0006H18V25.0006H25C26.105 25.0006 27 24.1056 27 23.0006V9.00058C27 7.8956 26.105 7.00061 25 7.00061Z" fill="currentColor"/>
<path d="M23 11.0006H9V12.6356C12.96 13.9156 16.085 17.0406 17.365 21.0006H23V11.0006Z" fill="currentColor"/>
</svg>`,Fe=`<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 32 32" fill="none">
<path d="M25 7H7C5.9 7 5 7.9 5 9V12H7V9H25V23H18V25H25C26.1 25 27 24.1 27 23V9C27 7.9 26.1 7 25 7ZM5 22V25H8C8 23.3 6.7 22 5 22ZM5 18V20C7.8 20 10 22.2 10 25H12C12 21.1 8.9 18 5 18ZM5 14V16C10 16 14 20 14 25H16C16 18.9 11.1 14 5 14Z" fill="currentColor"/>
</svg>`;var te=`<svg width="100%" height="100%" id="initialPlayButton" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M3.5 14C3.36739 14 3.24021 13.9473 3.14645 13.8536C3.05268 13.7598 3 13.6326 3 13.5V2.5C3.00001 2.41312 3.02267 2.32773 3.06573 2.25227C3.1088 2.17681 3.17078 2.11387 3.24558 2.06966C3.32037 2.02545 3.4054 2.00149 3.49227 2.00015C3.57915 1.9988 3.66487 2.02012 3.741 2.062L13.741 7.562C13.8194 7.60516 13.8848 7.66857 13.9303 7.74562C13.9758 7.82266 13.9998 7.91051 13.9998 8C13.9998 8.08949 13.9758 8.17734 13.9303 8.25438C13.8848 8.33143 13.8194 8.39484 13.741 8.438L3.741 13.938C3.66718 13.9786 3.58427 14 3.5 14Z" fill="currentColor"/>
</svg>`,le=`<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M6 19H10V5H6V19ZM14 5V19H18V5H14Z" fill="currentColor"></path>
</svg>`;function gt(e,t){if(!e)return;new IntersectionObserver((r,a)=>{r.forEach(s=>{s.isIntersecting&&(t(),a.disconnect())})}).observe(e)}var Yt=e=>{e.attachShadow({mode:"open"}),e.shadowRoot.appendChild(e.wrapper),e.shadowRoot.appendChild(e.customStyle)};async function Xt(e,t){e.hasAttribute("enable-lazy-loading")?gt(e,async()=>{Yt(e);let i=await t();e.streamUrlFinal=i}):(Yt(e),e.streamUrlFinal=await t())}var ei=["abort","canplay","canplaythrough","durationchange","emptied","ended","error","loadeddata","loadedmetadata","loadstart","pause","play","playing","progress","ratechange","seeked","seeking","stalled","suspend","timeupdate","volumechange","waiting","encrypted","waitingforkey"],ti=null;function ea(e,t){t&&Array.isArray(t)&&t.forEach(i=>{switch(i.field){case"playbackId":L(e,"Error loading the media. This can happen due to invalid Playback ID.");break;case"minResolution":L(e,"Error loading the media. This can happen due to invalid Minimum resolution.");break;case"maxResolution":L(e,"Error loading the media. This can happen due to invalid Maximum resolution.");break;case"resolution":L(e,"Error loading the media. This can happen due to invalid resolution.");break;case"order":L(e,'Error loading the media. This can happen due to invalid renditionOrder, it should be either "asc" or "desc".');break;default:break}})}function Jt(e,t){let i=[];if(e.hasAttribute("min-resolution")){let r=e.getAttribute("min-resolution");i.push(`minResolution=${r}`)}if(e.hasAttribute("max-resolution")){let r=e.getAttribute("max-resolution");i.push(`maxResolution=${r}`)}if(e.hasAttribute("resolution")){let r=e.getAttribute("resolution");i.push(`resolution=${r}`)}if(e.hasAttribute("rendition-order")){let r=e.getAttribute("rendition-order");i.push(`renditionOrder=${r}`)}if(i.length>0){let r=i.join("&");return t.includes("?")?`${t}&${r}`:`${t}?${r}`}return t}var ta=(e,t,i,r)=>{let a={422:()=>ea(e,r),401:()=>L(e,"Error loading the video. Incorrect playback ID or token."),400:()=>L(e,i?.includes("ready")?"The media is currently unavailable. Please wait until it's ready and then refresh the page.":"Invalid Playback URL. The provided playback URL is invalid or incorrectly formatted.")};[403,404].forEach(s=>{a[s]=()=>L(e,"Stream details not found. Playback ID is missing or invalid.")}),(a[t]||(()=>L(e,"Video stream couldn't be fetched. Please check your playback ID or internet connection.")))()},xt=async(e,t)=>{let i=await ia(e,t);return i.status===200?(e._src=t,t):(ta(e,i.status,i.errorMessage,i.errorFields),null)};async function ia(e,t){if(e.cache.has(t))return{status:e.cache.get(t),errorFields:null,errorMessage:null};try{let i=await fetch(t),r=i.headers.get("Content-Type")??"",a=await i.text();if(r.includes("application/json"))try{let s=JSON.parse(a);if(s?.success)return e._src=t,{status:i.status,errorFields:null,errorMessage:null};let o=s?.error?.message??"Unknown error occurred.",l=s?.error?.fields??null;return i.status===401&&t.includes("token")&&L(e,"Invalid playback URL. Please check the playback URL or verify if the token is invalid."),{status:i.status,errorFields:l,errorMessage:o}}catch{}return r.includes("application/vnd.apple.mpegurl")||r.includes("text/plain")?{status:i.status,playlist:a,errorMessage:null}:{status:i.status,errorFields:null,errorMessage:"Unexpected content type."}}catch{return L(e,"Network Error. Please check your internet connection and try refreshing the page."),{status:null,errorFields:null,errorMessage:"Network Error"}}}var ii=async(e,t,i,r)=>{let a=Jt(e,`${r}/${t}.m3u8`),s=i?Jt(e,`${r}/${t}.m3u8?token=${i}`):null;try{if(s&&i){let o=await xt(e,s);if(o)return o}return await xt(e,a)}catch{return L(e,"Network Error. Please check your internet connection and try refreshing the page."),null}};function ri(){let e=navigator.userAgent,t=/Chrome/.exec(e)||/CriOS/.exec(e),i=/Edg/.exec(e),r=/OPR|Opera/.exec(e);return!!window.chrome&&!!t&&!i&&!r}function Be(e){let t=/iPad|iPhone|iPod/.test(navigator.userAgent)&&!window.MSStream;return e.isiOS=t,t}function $(e){Array.isArray(e.playlist)&&(e.playlistPanel.innerHTML="",e.playlist.forEach((t,i)=>{let r=document.createElement("div");r.className="playlist-item",t.playbackId===e.playbackId&&r.classList.add("selected");let a=document.createElement("div");a.className="thumb",a.style.backgroundImage=`url('${t.thumbnail}')`;let s=document.createElement("div");s.className="info";let o=document.createElement("div");if(o.className="playlist-title",o.textContent=t.title,s.appendChild(o),t.duration){let l=document.createElement("div");l.className="playlist-item-duration",l.textContent=t.duration,s.appendChild(l)}r.appendChild(a),r.appendChild(s),r.addEventListener("click",l=>{if(l.preventDefault(),l.stopPropagation(),r.classList.contains("selected")){e.playlistPanel.classList.remove("open"),e.playlistPanel.classList.add("closing"),setTimeout(()=>{e.playlistPanel.classList.remove("closing")},200);return}e.selectEpisodeByPlaybackId(t.playbackId),e.playlistPanel.classList.remove("open"),e.playlistPanel.classList.add("closing"),setTimeout(()=>{e.playlistPanel.classList.remove("closing")},200)}),e.playlistPanel.appendChild(r)}))}var ra=(e,t,i,r)=>ii(e,t,i,r),aa=(e,t,i,r)=>ii(e,t,i,r);async function sa(e,t,i,r,a){return a==="on-demand"?await ra(e,t,i,r):a==="live-stream"?await aa(e,t,i,r):(L(e,"Unsupported stream type"),e.video.poster="",null)}async function Ne(e,t,i,r,a){let s=await sa(e,t,i,r,a);return s?(ti=s,oa(e,s,a),s):null}function qe(){return ti}function oa(e,t,i){e.hasAttribute("enable-lazy-loading")?gt(e.video,()=>{vt(e,t,i)}):vt(e,t,i)}function J(e){let t=Math.floor(e/3600),i=Math.floor(e%3600/60),r=Math.floor(e%60),a=t>0?`${t}:`:"",s=i.toString().padStart(2,"0"),o=r.toString().padStart(2,"0");return`${a}${s}:${o}`}function Ct(e,t){let i=["progressBarContainer","volumeControl","volumeButton","pipButton","fullScreenButton","ccButton","fastForwardButton","rewindBackButton","playPauseButton","timeDisplay","parentVolumeDiv","playbackRateButton","volumeiOSButton","resolutionMenuButton","audioMenuButton","titleElement","mobileControls","leftControls","resolutionMenu","playbackRateDiv","liveStreamDisplay","subtitleMenu","playlistButton","castButton","playlistSlot"],r=t?"1":"0",a=t?"opacity 0.9s ease":"";i.forEach(s=>{let o=e[s];if(o){if(s==="playlistSlot"){let l=t&&!!e.externalPlaylistOpen;o.style.opacity=l?"1":"0",o.style.transition=a;return}o.style.opacity=r,o.style.transition=a}})}function ue(e,t){if(V())si(t);else{let i=Math.min(Math.max(e.video.currentTime+t,0),e.video.duration);e.video.currentTime=i}}function na(e,t){return isNaN(e)?isNaN(t)?"0:00":J(t):J(e)}function Y(e){let t=e?.video?.duration;return typeof t=="number"&&!isNaN(t)&&(t>0||t===1/0)}function ge(e){let t;V()?t=Math.floor(kt()):t=Math.floor(e.video.currentTime);let i=Math.floor(e.video.duration),r=e.getAttribute("default-show-remaining-time")!==null,a,s;if(r){let o=i-t,l=!isNaN(o);a=l?"-"+J(o):"0:00",s=l?J(i):"0:00"}else a=isNaN(t)?"0:00":J(t),s=na(i,e.defaultDuration);if(e.timeDisplay.textContent=`${a} / ${s}`,e.video.buffered.length>0){let o=e.video.buffered.end(0)/i*100;e.bufferedRange.style.width=`${o}%`}}function ai(e){e.mutedAttribute=e.hasAttribute("muted"),e.hasAutoPlayAttribute=e.hasAttribute("auto-play"),e.loopAttribute=e.hasAttribute("loop"),e.disableVideoClickAttr=e.hasAttribute("disable-video-click"),e.enableCacheBusting=e.hasAttribute("enable-cache-busting"),e.controlsContainerValue=oi(e),e.hideControlAttr=e.hasAttribute("hide-controls"),e.loopPlaylistTillEnd=e.hasAttribute("loop-next"),e.token=e.getAttribute("token"),e.drmToken=e.getAttribute("drm-token"),e.playbackId=e.getAttribute("playback-id"),e.defaultPlaybackId=e.getAttribute("default-playback-id"),e.defaultStreamType=e.getAttribute("default-stream-type")??"on-demand",e.streamType=e.getAttribute("stream-type")??e.defaultStreamType??"on-demand",e.debugAttribute=e.hasAttribute("debug"),e.startTimeAttribute=e.hasAttribute("start-time")?e.getAttribute("start-time"):0,e.hideDefaultPlaylistPanel=e.hasAttribute("hide-default-playlist-panel"),e.thumbnailTime=e.getAttribute("thumbnail-time")??e.startTimeAttribute,e.getThumbnailAttribute=e.getAttribute("thumbnail-time"),e.thumbnailTimeAttribute=parseFloat(e.getThumbnailAttribute)||parseFloat(e.thumbnailTime),e.posterAttribute=e.getAttribute("poster"),e.placeholderAttribute=e.getAttribute("placeholder"),e.thumbnailUrlAttribute=e.getAttribute("spritesheet-src");let t=e?.thumbnailUrlAttribute??"images.fastpix.io";e.thumbnailUrlFinal=`https://${t}`,e.playbackRatesAttribute=e.getAttribute("playback-rates"),e.defaultPlaybackRateAttribute=e.getAttribute("default-playback-rate"),e.titleText=e.getAttribute("title"),e.preloadAttribute=e.getAttribute("preload"),e.crossoriginAttribute=e.getAttribute("crossorigin");let i="#5D09C7",r="#F5F5F5",a="transparent";e.accentColor=e.getAttribute("accent-color")??i,e.primaryColor=e.getAttribute("primary-color")??r,e.secondaryColor=e.getAttribute("secondary-color")??a,e.style.setProperty("--accent-color",e.accentColor),e.style.setProperty("--primary-color",e.primaryColor),e.style.setProperty("--secondary-color",e.secondaryColor),e.defaultDuration=e.getAttribute("default-duration"),e.disableKeyboardControls=e.hasAttribute("disable-keyboard-controls")&&e.getAttribute("disable-keyboard-controls")!=="false";let s=e.getAttribute("hot-keys");e.hotKeys=e.hasAttribute("hot-keys")?s?.split(" "):[],e.forwardSeekAttribute=e.getAttribute("forward-seek-offset"),e.backwardSeekAttribute=e.getAttribute("backward-seek-offset");let o=e.getAttribute("skip-intro-start"),l=e.getAttribute("skip-intro-end"),u=o!=null?parseFloat(o):NaN,n=l!=null?parseFloat(l):NaN;e.skipIntroStart=Number.isFinite(u)?u:null,e.skipIntroEnd=Number.isFinite(n)?n:null;let d=e.getAttribute("next-episode-button-overlay"),f=d!=null?parseFloat(d):NaN;e.nextEpisodeOverlayStart=Number.isFinite(f)?f:null}function wt(e){e.config.drmSystems["com.widevine.alpha"].licenseUrl=`https://api.fastpix.io/v1/on-demand/drm/license/widevine/${e.playbackId}?token=${e.drmToken}`,e.config.drmSystems["com.apple.fps"].licenseUrl=`https://api.fastpix.io/v1/on-demand/drm/license/fairplay/${e.playbackId}?token=${e.drmToken}`,e.config.drmSystems["com.apple.fps"].serverCertificateUrl=`https://api.fastpix.io/v1/on-demand/drm/cert/fairplay/${e.playbackId}?token=${e.drmToken}`}var Le=`<svg width="100%" height="100%" viewBox="0 0 44 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.2227 21.334H14.6671V16.0007H19.556V13.334H12.2227V21.334Z" fill="currentColor"/>
    <path d="M12.2227 21.334H14.6671V16.0007H19.556V13.334H12.2227V21.334Z" fill="currentColor"/>
    <path d="M24.4443 13.334V16.0007H29.3332V21.334H31.7777V13.334H24.4443Z" fill="currentColor"/>
    <path d="M29.3332 31.9993H24.4443V34.666H31.7777V26.666H29.3332V31.9993Z" fill="currentColor"/>
    <path d="M29.3332 31.9993H24.4443V34.666H31.7777V26.666H29.3332V31.9993Z" fill="currentColor"/>
    <path d="M14.6671 26.666H12.2227V34.666H19.556V31.9993H14.6671V26.666Z" fill="currentColor"/>
    <path d="M14.6671 26.666H12.2227V34.666H19.556V31.9993H14.6671V26.666Z" fill="currentColor"/>
    <path d="M24.4443 13.334V16.0007H29.3332V21.334H31.7777V13.334H24.4443Z" fill="currentColor"/>
</svg>`,ni=`<svg width = "100%" height = "100%" viewBox = "0 0 49 48" fill = "none" xmlns = "http://www.w3.org/2000/svg" ><path d="M19.6115 18.6673H14.7227V21.334H22.056V13.334H19.6115V18.6673Z" fill="currentColor"/><path d="M18.6115 13.334V17.6673H14.7227H13.7227V18.6673V21.334V22.334H14.7227H22.056H23.056V21.334V13.334V12.334H22.056H19.6115H18.6115V13.334Z" stroke="black" stroke-opacity="0.15" stroke-width="2"/><path d="M19.6115 18.6673H14.7227V21.334H22.056V13.334H19.6115V18.6673Z" fill="currentColor"/><path d="M18.6115 13.334V17.6673H14.7227H13.7227V18.6673V21.334V22.334H14.7227H22.056H23.056V21.334V13.334V12.334H22.056H19.6115H18.6115V13.334Z" stroke="black" stroke-opacity="0.15" stroke-width="2"/><path d="M29.3888 18.6673V13.334H26.9443V21.334H34.2777V18.6673H29.3888Z" fill="currentColor"/><path d="M34.2777 17.6673H30.3888V13.334V12.334H29.3888H26.9443H25.9443V13.334V21.334V22.334H26.9443H34.2777H35.2777V21.334V18.6673V17.6673H34.2777Z" stroke="black" stroke-opacity="0.15" stroke-width="2"/><path d="M29.3888 18.6673V13.334H26.9443V21.334H34.2777V18.6673H29.3888Z" fill="currentColor"/><path d="M34.2777 17.6673H30.3888V13.334V12.334H29.3888H26.9443H25.9443V13.334V21.334V22.334H26.9443H34.2777H35.2777V21.334V18.6673V17.6673H34.2777Z" stroke="black" stroke-opacity="0.15" stroke-width="2"/><path d="M26.9443 34.666H29.3888V29.3327H34.2777V26.666H26.9443V34.666Z" fill="currentColor"/><path d="M25.9443 34.666V35.666H26.9443H29.3888H30.3888V34.666V30.3327H34.2777H35.2777V29.3327V26.666V25.666H34.2777H26.9443H25.9443V26.666V34.666Z" stroke="black" stroke-opacity="0.15" stroke-width="2"/><path d="M26.9443 34.666H29.3888V29.3327H34.2777V26.666H26.9443V34.666Z" fill="currentColor"/><path d="M25.9443 34.666V35.666H26.9443H29.3888H30.3888V34.666V30.3327H34.2777H35.2777V29.3327V26.666V25.666H34.2777H26.9443H25.9443V26.666V34.666Z" stroke="black" stroke-opacity="0.15" stroke-width="2"/><path d="M14.7227 29.3327H19.6115V34.666H22.056V26.666H14.7227V29.3327Z" fill="currentColor"/><path d="M13.7227 29.3327V30.3327H14.7227H18.6115V34.666V35.666H19.6115H22.056H23.056V34.666V26.666V25.666H22.056H14.7227H13.7227V26.666V29.3327Z" stroke="black" stroke-opacity="0.15" stroke-width="2"/>
    <path d="M13.7227 29.3327V30.3327H14.7227H18.6115V34.666V35.666H19.6115H22.056H23.056V34.666V26.666V25.666H22.056H14.7227H13.7227V26.666V29.3327Z" stroke="black" stroke-opacity="0.15" stroke-width="2"/>
    <path d="M14.7227 29.3327H19.6115V34.666H22.056V26.666H14.7227V29.3327Z" fill="currentColor"/>
</svg >`;function li(e){Array.from(e.video.textTracks).forEach(i=>{(i.kind==="subtitles"||i.kind==="captions")&&(i.mode="hidden")})}function ui(e){let t=Array.from(e.video.textTracks);for(let r of t)r.mode="hidden";let i=m.createElement("style");i.textContent=`
    /* Hide cues in all browsers */
    video::cue {
      display: none !important;
    }

    /* WebKit-based browsers (Chrome, Safari) */
    video::-webkit-media-text-track-display {
      display: none !important;
      background: none !important;
      color: red !important;
      text-shadow: none !important;
      box-shadow: none !important;
      border: none !important;
      outline: none !important;
    }

    /* Firefox */
    video::cue {
      background: none !important;
      color: red !important;
      text-shadow: none !important;
      box-shadow: none !important;
      border: none !important;
      outline: none !important;
  }`,document.head.appendChild(i)}function St(e,t){t.length>0&&(t[0].mode="showing",t[0].default=!0,e.currentSubtitleTrackIndex=0,localStorage.setItem("subtitleLang",t[0].language))}function di(e){e.wrapper.classList.add("subtitles-up")}function pi(e){e.wrapper.classList.remove("subtitles-up")}function ve(e,t){let i=Array.from(e.video.textTracks);for(let r of i)r.mode="disabled";if(e.subtitleContainer&&(e.subtitleContainer.innerHTML="",e.subtitleContainer.classList.remove("contained")),localStorage.removeItem("subtitleLang"),t?.emitEvent)try{let r=typeof e.getSubtitleTracks=="function"?e.getSubtitleTracks():[],a=e.currentSubtitleTrackId!==void 0?e.currentSubtitleTrackId:null;e.dispatchEvent(new CustomEvent("fastpixsubtitlechange",{detail:{tracks:r,currentId:a,currentTrack:null}}))}catch{}}function ze(e,t,i){e.subtitleMenu.style.display="none";let r=Array.from(e.video.textTracks);for(let a=0;a<r.length;a++){let s=r[a];a===t?(s.mode="showing",e.currentSubtitleTrackIndex=t):s.mode="disabled"}if(i?.emitEvent)try{let a=typeof e.getSubtitleTracks=="function"?e.getSubtitleTracks():[],s=e.currentSubtitleTrackId!==void 0?e.currentSubtitleTrackId:null,o=Array.isArray(a)?a.find(l=>l?.isCurrent)??null:null;e.dispatchEvent(new CustomEvent("fastpixsubtitlechange",{detail:{tracks:a,currentId:s,currentTrack:o}}))}catch{}}function la(e,t){let i=Object.values(t).filter(r=>r!==null&&typeof r=="object"&&"mode"in r&&"kind"in r&&"label"in r&&"language"in r);e.subtitleContainer&&(e.subtitleContainer.innerHTML="",e.subtitleContainer.classList.remove("contained")),i.forEach((r,a)=>{let s=document.getElementById(`track-${a}`);s&&(r.mode==="showing"?s.classList.add("active"):s.classList.remove("active"))})}function hi(e){let t=Array.from(e.video.textTracks),i=e.currentSubtitleTrackIndex;if(i===-1)return;let r=t[i];r.mode==="showing"?r.mode="disabled":r.mode="showing",la(e,t)}function $e(e){let t=e.wrapper;document.fullscreenElement?(document.exitFullscreen(),t.classList.remove("fullscreen")):(t.requestFullscreen().catch(i=>{L(e,"Error attempting to enable full-screen mode:")}),e.fullScreenButton.innerHTML=Le,t.classList.add("fullscreen"))}function Ze(e){e.audioMenu.style.display==="none"?e.audioMenu.style.display="flex":e.audioMenu.style.display="none"}function Ke(e){e.resolutionMenu.style.display==="none"?e.resolutionMenu.style.display="flex":e.resolutionMenu.style.display="none"}function fi(e){e.playbackRateDiv.style.display==="none"?e.playbackRateDiv.style.display="flex":e.playbackRateDiv.style.display="none"}function Tt(e){e.wrapper.classList.add("initialized"),e.playPauseButton.classList.add("initialized"),e.bottomRightDiv.classList.add("initialized"),e.titleElement.classList.add("initialized"),e.leftControls.classList.add("initialized"),e.progressBar.classList.add("initialized"),e.parentVolumeDiv.classList.add("initialized")}function Ge(e){if(!window?.cast?.framework?.RemotePlayer)return{remotePlayer:null,remotePlayerController:null};let t=new window.cast.framework.RemotePlayer,i=new window.cast.framework.RemotePlayerController(t);return{remotePlayer:t,remotePlayerController:i}}function ua(e){let{remotePlayer:t,remotePlayerController:i}=Ge(e);t?.playerState==="PAUSED"&&t?.isPaused&&t?.playerState!=="PLAYING"?(i.playOrPause(),e.pausedOnCasting=!1,e.playPauseButton.innerHTML=le,localStorage.setItem("pausedOnCasting","false")):(i.playOrPause(),e.pausedOnCasting=!0,e.playPauseButton.innerHTML=te,localStorage.setItem("pausedOnCasting","true"))}function mi(e,t,i,r){Tt(e),!e.isLoading&&(e.video.paused?(e.initialPlayClick||(R(e),Qe(e)),e.video.readyState>=3?e.video.play().then(()=>{P(e),e.initialPlayClick=!0,je(e,e.video.offsetWidth,t,i,r)}).catch(a=>{P(e)}):e.video.addEventListener("canplay",()=>{e.video.play().then(()=>{P(e),e.initialPlayClick=!0,je(e,e.video.offsetWidth,t,e.thumbnailUrlFinal,r)}).catch(a=>{P(e)})},{once:!0}),e.playPauseButton.innerHTML=le):(e.video.pause(),e.playPauseButton.innerHTML=te),e.video.addEventListener("canplay",()=>{e.isLoading=!1,Y(e)&&P(e),e.initialPlayClick&&je(e,e.video.offsetWidth,t,e.thumbnailUrlFinal,r)}))}function yi(e){e.nextButton.addEventListener("click",()=>{try{if(typeof e.customNext=="function"){e.customNext.call(e,e);return}}catch{}e.next()})}function bi(e){e.prevButton.addEventListener("click",()=>{try{if(typeof e.customPrev=="function"){e.customPrev.call(e,e);return}}catch{}e.previous()})}function gi(e){e._externalPlaylistOutsideHandlerRegistered||(e.wrapper.addEventListener("click",t=>{if(!e.hideDefaultPlaylistPanel||!e.externalPlaylistOpen)return;let i=t.target,r=i===e.playlistButton||e.playlistButton.contains(i),a=e.playlistSlot?Array.from(e.playlistSlot.children):[],s=a.some(o=>o.contains(i));!r&&!s&&(e.externalPlaylistOpen=!1,a.forEach(o=>o.style.pointerEvents="none"),e.dispatchEvent(new CustomEvent("playlisttoggle",{detail:{open:!1,hasPlaylist:Array.isArray(e.playlist)&&e.playlist.length>0,currentIndex:e.currentIndex,totalItems:Array.isArray(e.playlist)?e.playlist.length:0,playbackId:e.playbackId??null},bubbles:!0,composed:!0})))},!0),e._externalPlaylistOutsideHandlerRegistered=!0),e.playlistButton.addEventListener("click",()=>{if(e.hideDefaultPlaylistPanel||!e.playlistPanel){let i=!e.externalPlaylistOpen;e.externalPlaylistOpen=i,(e.playlistSlot?Array.from(e.playlistSlot.children):[]).forEach(a=>a.style.pointerEvents=i?"auto":"none"),e.dispatchEvent(new CustomEvent("playlisttoggle",{detail:{open:i,hasPlaylist:Array.isArray(e.playlist)&&e.playlist.length>0,currentIndex:e.currentIndex,totalItems:Array.isArray(e.playlist)?e.playlist.length:0,playbackId:e.playbackId??null},bubbles:!0,composed:!0}));return}e.playlistPanel.classList.contains("open")?(e.playlistPanel.classList.remove("open"),e.playlistPanel.classList.add("closing"),setTimeout(()=>{e.playlistPanel.classList.remove("closing")},200)):(M(e),$(e),e.playlistPanel&&(e.playlistPanel.style.display="block",e.playlistPanel.classList.add("open")))})}function x(e,t,i,r){let a=/^((?!chrome|android).)*safari/i.test(navigator.userAgent),s=Be(e),o=Ci(),l=s?null:de(),{remotePlayer:u}=s?{remotePlayer:null}:Ge(e);if(a||!o){mi(e,t,i,r);return}if(!s&&o&&u?.canSeek!==!1){ua(e);return}localStorage.removeItem("pausedOnCasting"),!s&&l&&l.endCurrentSession(!0),mi(e,t,i,r)}function vi(e){if(e.subtitleMenu.style.display==="flex"){e.subtitleMenu.style.display="none";return}if(!e.video?.textTracks)return;for(;e.subtitleMenu.firstChild;)e.subtitleMenu.removeChild(e.subtitleMenu.firstChild);let t=m.createElement("button");t.textContent="Off",t.className="offSubtitles",t.addEventListener("click",()=>{ve(e,{emitEvent:!0}),e.subtitleMenu.style.display="none"}),e.subtitleMenu.appendChild(t);let i=Array.from(e.video.textTracks),r=i.some(a=>a.mode==="showing");for(let a=0;a<i.length;a++){let s=i[a],o=m.createElement("button");o.className="subtitleSelectorButtons",o.textContent=s.label??`Language ${a+1}`,o.addEventListener("click",()=>{ze(e,a,{emitEvent:!0})}),s.mode==="showing"&&(o.classList.add("active"),e.currentSubtitleTrackIndex=a),e.subtitleMenu.appendChild(o)}r||t.classList.add("active"),e.subtitleMenu.style.display="flex",e.subtitleMenu.className="subtitle-menu",e.subtitleMenu.style.flexDirection="column",e.subtitleMenu.style.color="#000"}var oe="[Cast]",da=!1,Pe=!1;function pa(e){Pe=!!e?.debugAttribute}function _e(e,t,i){if(!Pe)return;let r=`--- STEP ${e} ---`;da||void 0}function X(...e){}function Bt(...e){}function wi(...e){}var ki=!1;function Ei(){if(window?.cast?.framework||window.__fastpixCastLoading||ki||document.querySelector('script[src*="cast_sender.js"][data-fastpix-cast="true"]'))return;let e=document.createElement("script");e.src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1",e.async=!0,e.defer=!0,e.dataset.fastpixCast="true",window.__fastpixCastLoading=!0,ki=!0,e.onload=()=>{window.__fastpixCastLoading=!1},e.onerror=()=>{window.__fastpixCastLoading=!1,X(oe,"Cast sender script failed to load")},document.head.appendChild(e)}var Ye=!1,Ae=null,Lt=null;function ca(){Ae!==null&&(clearInterval(Ae),Ae=null),Lt=null}function Ti(e,t,i,r){pa(r),!Be(r)&&(window.__onGCastApiAvailable=()=>{ha(e,t,i,r)})}function ma(){return!!window.chrome?.cast&&!!window.chrome.cast.isAvailable}var Ci=()=>{let e=window?.cast?.framework?.CastContext?.getInstance?.();if(!e)return!1;let t=e.getCastState?.();return t==="AVAILABLE"||t==="CONNECTED"};function ha(e,t,i,r){if(Be(r)){return}ma()?ba(e,t,i,r):fa()}function fa(){X("Google Cast API did NOT load.")}function V(){return!!de()?.getCurrentSession()}function Bi(e,t){t.__fpCastFreezeProgressInterval!=null&&(clearInterval(t.__fpCastFreezeProgressInterval),t.__fpCastFreezeProgressInterval=null);function i(){if(!V())return;let r=window.cast.framework.CastContext.getInstance().getCurrentSession();if(r){let a=r.getMediaSession();if(a){let s=a.getEstimatedTime();t.progressBar.value=s/e.duration*100,t.textContent=J(s)}}}t.__fpCastFreezeProgressInterval=window.setInterval(()=>{requestAnimationFrame(i)},1e3)}function Xe(e){let t=window.cast.framework.CastContext.getInstance().getCurrentSession();if(!t)return;let i=t.getMediaSession();if(i){let r=new window.chrome.cast.media.SeekRequest;r.currentTime=e,i.seek(r,()=>{},a=>X("[Cast] Seek failed",a))}}function ya(e,t){let i=de(),r=window.cast.framework.SessionState;i.addEventListener(window.cast.framework.CastContextEventType.SESSION_STATE_CHANGED,a=>{let s=i.getCurrentSession(),o=window,l=o.__fastpixCastingPlayerContext;switch(a.sessionState,s?.getMediaSession?.(),a.sessionState){case r.SESSION_STARTED:case r.SESSION_RESUMED:{if(t.currentCastSession=s,l!=null&&l!==t){e.pause();break}if(Ae!==null){e.pause();break}let{isMuted:u,mediaVolume:n}=Li(t),d=Math.min(Math.max(n,0),1);localStorage.setItem("chromecastFinished","false"),U=e.currentTime,localStorage.setItem("chromecastActive","true"),Bi(e,t),Z(d,u),e.pause(),Lt={video:e,playerContext:t};let f=null;Ae=window.setInterval(()=>{requestAnimationFrame(()=>{let p=Lt;if(!p)return;let S=de()?.getCurrentSession?.()?.getMediaSession?.();if(!S)return;let w=p.playerContext,g=p.video,k=Ge(w).remotePlayer,y=S.playerState;y!==f&&(S.getEstimatedTime?.(),f=y),U=S.getEstimatedTime(),S.playerState==="BUFFERING"?R(w):P(w),w.pausedOnCasting=S.playerState==="PAUSED";let h=w.loopEnabled??k?.isLoopingEnabled;if(k?.duration&&Math.floor(U)>=Math.floor(k.duration)&&!h&&!Ye){let v=new Event("ended");g.dispatchEvent(v),Ye=!0,Qe(w),R(w),localStorage.setItem("chromecastFinished","true"),localStorage.setItem("chromecastActive","false"),R(w)}k?.duration&&Math.floor(U)<Math.floor(k.duration)&&Ye&&(Ye=!1),g.dispatchEvent(new Event("timeupdate"))})},1e3);break}case r.SESSION_ENDED:{let u=s?.getMediaSession();u?.getEstimatedTime?.(),localStorage.getItem("chromecastActive")==="true"&&localStorage.setItem("chromecastActive","false"),U=u?.getEstimatedTime()??U,t.currentCastSession=null;let n=o.__fastpixCastingPlayerContext,d=o.__fastpixCastingVideo;if(n==null||n===t){n?.__fpCastFreezeProgressInterval!=null&&(clearInterval(n.__fpCastFreezeProgressInterval),n.__fpCastFreezeProgressInterval=null),ca();let p=d??e;p.currentTime=U,localStorage.setItem("media-volume",p.volume.toString()),o.__fastpixCastingPlayerContext=null,o.__fastpixCastingVideo=null}t.pausedOnCasting?e.pause():e.play();break}}})}function Li(e){let t=localStorage.getItem("media-volume"),i=t!==null?parseFloat(t):1,r=i===0;return e.isMuted=r,{isMuted:r,mediaVolume:i}}function Z(e,t){if(!V())return;let i=window.chrome.cast,r=window.cast.framework.CastContext.getInstance().getCurrentSession(),a=10,s=0,o=()=>{if(!r)return;let l=r.getMediaSession();if(l!==null){let u=new i.media.VolumeRequest(new i.Volume);u.volume.level=e,u.volume.muted=t,l.setVolume(u,()=>{},n=>X("\u274C Chromecast: Volume update failed",n))}else s<a&&(s++,setTimeout(o,300))};o()}function _i(e){let t=window.cast.framework.CastContext.getInstance().getCurrentSession();t&&t.setVolume(e).then(()=>{}).catch(i=>X("Volume change error:",i))}function ba(e,t,i,r){let a=de(),s=window.chrome?.cast;if(!a||!s){X("Chromecast API is not available.");return}ya(t,r);let o=r.castReceiverAppId??"CC1AD845";a.setOptions({receiverApplicationId:o,autoJoinPolicy:s.AutoJoinPolicy.ORIGIN_SCOPED,androidReceiverCompatible:!0,language:"en-US",resumeSavedSession:!0}),_e(1,"Receiver",{receiverAppId:o}),a.addEventListener(window.cast.framework.CastContextEventType.CAST_STATE_CHANGED,l=>Si(e,l.castState,r)),Si(e,a.getCastState(),r),e.addEventListener("click",()=>ga(a,e,t,i,r))}function Si(e,t,i){let r=window.cast?.framework,a=r?.CastState?.NO_DEVICES_AVAILABLE,s=r?.CastState?.NOT_SUPPORTED,o=t!==a&&t!==s&&(t===r?.CastState?.CONNECTED||t===r?.CastState?.NOT_CONNECTED||t===r?.CastState?.CONNECTING);i?.castButton?.style?.setProperty("--cast-button-display",o?"flex":"none"),e.innerHTML=t===r?.CastState?.CONNECTED?bt:Fe}function de(){return window.cast?.framework?.CastContext?.getInstance()}function ga(e,t,i,r,a){let s=de(),o=s.getCurrentSession();i.currentSrc||i.src,M(a),o?s.requestSession().catch(l=>X(oe,"Error opening session menu",l)):(window.__fastpixCastingPlayerContext=a,window.__fastpixCastingVideo=i,s.requestSession().then(()=>wa(e,i,r,t,a)).catch(l=>{let u=String(l?.message||l).toLowerCase();u==="cancel"||u.includes("cancel")?void 0:X(oe,"Unable to start casting session",l)}))}function Me(e,t){let i=window.cast.framework.CastContext.getInstance().getCurrentSession();if(!i)return;let r=i.getMediaSession();r&&(e==="play"?(r.play(null,()=>{},wi),t.playPauseButton.innerHTML=le):(r.pause(null,()=>{},wi),t.playPauseButton.innerHTML=te))}var U=0;function si(e){let i=de().getCurrentSession();if(i){let r=i.getMediaSession();if(r){let a=r.getEstimatedTime(),s=Math.max(0,a+e),o=new window.chrome.cast.media.SeekRequest;o.currentTime=s,r.seek(o,()=>{},l=>X("Chromecast: Seek failed",l))}}}function va(e){try{let t=e?.config?.drmSystems;if(!t)return null;let i=t["com.widevine.alpha"],r=t["com.microsoft.playready"];if(!i&&!r)return null;let a={};return i?.licenseUrl&&(a.widevineLicenseUrl=i.licenseUrl,i.licenseUrl.substring(0,80),void 0),r?.licenseUrl&&(a.playReadyLicenseUrl=r.licenseUrl,void 0),a}catch(t){return Bt(oe,"Failed to extract DRM config from hls.js",t),null}}function Ca(e){let t={};return(e.widevineLicenseUrl||e.playReadyLicenseUrl)&&(t.drm={},e.widevineLicenseUrl&&(t.drm.widevine={licenseUrl:e.widevineLicenseUrl},e.licenseRequestHeaders&&(t.drm.widevine.headers=e.licenseRequestHeaders),e.licenseRequestData&&(t.drm.widevine.licenseRequestData=e.licenseRequestData),e.widevineLicenseUrl.substring(0,80),e.licenseRequestHeaders,void 0),e.playReadyLicenseUrl&&(t.drm.playready={licenseUrl:e.playReadyLicenseUrl},e.licenseRequestHeaders&&(t.drm.playready.headers=e.licenseRequestHeaders),void 0)),t}function wa(e,t,i,r,a){let s=e.getCurrentSession();if(!s){X(oe,"No session available.");return}let o=a.hls&&typeof a.hls.url=="string"?a.hls.url:"",l=o&&o.trim()!==""?o:i&&String(i).trim()!==""?i:t.currentSrc||t.src;if(!l||l.trim()===""){X(oe,"No stream URL. Ensure the video has loaded and try casting again.");return}U=t.currentTime,window.__fastpixCastingPlayerContext=a,window.__fastpixCastingVideo=t;let u=!0,n=l;(function(){_e(2,"URL sent to Chromecast",{url:n.substring(0,90)+(n.length>90?"...":""),isMaster:n.toLowerCase().includes(".m3u8")}),n.substring(0,80)+(n.length>80?"...":""),/^(https?:\/\/)?(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(n)&&Bt(oe,"Stream URL is localhost \u2014 Chromecast cannot reach it.");let f=window.chrome.cast,p=new f.media.MediaInfo(n,"application/vnd.apple.mpegurl");p.streamType=f.media.StreamType.BUFFERED,p.metadata=new f.media.GenericMediaMetadata,p.hlsSegmentFormat=f.media.HlsSegmentFormat.FMP4,p.hlsVideoSegmentFormat=f.media.HlsVideoSegmentFormat.FMP4;let C=a.drmConfig;if(!C&&a.hls&&(C=va(a.hls)??void 0),C&&(C.widevineLicenseUrl||C.playReadyLicenseUrl)){let y=Ca(C);p.customData=y,y.drm?.widevine,y.drm?.playready}let S=Array.from(t.textTracks),w=Array.from(t.querySelectorAll("track")),g=[];if(w.length>0)for(let y=0;y<w.length;y++){let h=w[y],v=new f.media.Track(y+1,f.media.TrackType.TEXT);v.trackContentId=h.src||"",v.trackContentType="text/vtt",v.name=h.label||`Subtitle ${y+1}`,v.language=h.srclang||"en",v.subtype=f.media.TextTrackType.SUBTITLES,g.push(v)}else if(S.length>0)for(let y=0;y<S.length;y++){let h=S[y],v=new f.media.Track(y+1,f.media.TrackType.TEXT);v.trackContentId="",v.trackContentType="text/vtt",v.name=h.label||`Subtitle ${y+1}`,v.language=h.language||"en",v.subtype=f.media.TextTrackType.SUBTITLES,g.push(v)}g.length>0&&(p.tracks=g,a.currentCastSession=s);let k=new f.media.LoadRequest(p);k.currentTime=0,k.autoplay=u,k.currentTime,k.autoplay,C?.widevineLicenseUrl||C?.playReadyLicenseUrl,_e(3,"Sending loadMedia request",{currentTime:0,autoplay:!0}),s.loadMedia(k).then(()=>{_e(3,"loadMedia accepted by receiver (waiting for media session)",null),r.innerHTML=bt,t.pause(),Bi(t,a);let y=(A,z)=>{if(!A)return;let W={playerState:A.playerState,estimatedTime:A.getEstimatedTime?.()};A.idleReason!=null&&(W.idleReason=A.idleReason,W._hint="idleReason set = receiver had a problem (LOAD_FAILED / CORS / DRM license failure)")},h=0,v=!1,T=window.setInterval(()=>{requestAnimationFrame(()=>{let A=s.getMediaSession();A?(y(A,`TV status #${++h}`),A.playerState==="PLAYING"&&(v=!0,window.clearInterval(T),void 0),typeof A.addUpdateListener=="function"&&h===1&&A.addUpdateListener(z=>{A.playerState,A.idleReason})):(++h,void 0),h>=10&&window.clearInterval(T)})},2e3),E=A=>{let z=s.getMediaSession();if(z){if(y(z,"tryPlay"),z.playerState==="PLAYING"){U>0&&setTimeout(()=>Xe(U),300);return}z.play(null,()=>{U>0&&setTimeout(()=>Xe(U),300)},W=>Bt(oe,"media.play() error",W))}else A<6?setTimeout(()=>E(A+1),600):void 0};E(0);let{mediaVolume:B,isMuted:O}=Li(a);Z(B,O);let N=new window.cast.framework.RemotePlayer,q=new window.cast.framework.RemotePlayerController(N);N.volumeLevel!==B&&(N.volumeLevel=B,q.setVolumeLevel())}).catch(y=>{_e(3,"loadMedia FAILED",{error:String(y?.message||y)}),X(oe,"Load media error",y)})})()}function kt(){return U}function _t(e){let t;return V()?t=kt():t=e.video.currentTime,t}function Ce(e){return e.streamType==="live-stream"?"none":""}function pe(e,t){e&&t&&e.contains(t)&&e.removeChild(t)}function Ai(e){e&&(e.style.display="none")}function ka(e){e.cartButton&&(e.getAttribute?e.getAttribute("theme"):null)!=="shoppable-shorts"&&(e.cartButton.style.display="none")}function Sa(e){window.innerWidth>768||(pe(e.leftControls,e.parentVolumeDiv),pe(e.leftControls,e.forwardRewindControlsWrapper),pe(e.leftControls,e.prevButton),pe(e.leftControls,e.nextButton),pe(e.leftControls,e.timeDisplay),pe(e.mobileControlButtonsBlock,e.forwardRewindControlsWrapper),pe(e.forwardRewindControlsWrapper,e.rewindBackButton),pe(e.forwardRewindControlsWrapper,e.fastForwardButton),ka(e),Ai(e.subtitleContainer),Ai(e.forwardRewindControlsWrapper))}function Ea(e){window.innerWidth>768||(e.leftControls.contains(e.forwardRewindControlsWrapper)&&e.leftControls.removeChild(e.forwardRewindControlsWrapper),e.mobileControlButtonsBlock.contains(e.forwardRewindControlsWrapper)&&e.mobileControlButtonsBlock.removeChild(e.forwardRewindControlsWrapper),e.leftControls.contains(e.timeDisplay)&&e.leftControls.removeChild(e.timeDisplay),e.forwardRewindControlsWrapper.contains(e.rewindBackButton)&&e.forwardRewindControlsWrapper.removeChild(e.rewindBackButton),e.forwardRewindControlsWrapper.hasChildNodes(e.fastForwardButton)&&e.forwardRewindControlsWrapper.removeChild(e.fastForwardButton),e.cartButton&&(e.cartButton.style.display="flex"),e.subtitleContainer&&(e.subtitleContainer.style.display="block"),e.forwardRewindControlsWrapper&&(e.forwardRewindControlsWrapper.style.display="none"))}function Ta(e){e.controlsContainer.getElementsByClassName("timeDisplay").length>0&&e.leftControls.removeChild(e.timeDisplay),e.forwardRewindControlsWrapper.hasChildNodes(e.rewindBackButton)&&e.forwardRewindControlsWrapper.removeChild(e.rewindBackButton),e.forwardRewindControlsWrapper.hasChildNodes(e.fastForwardButton)&&e.forwardRewindControlsWrapper.removeChild(e.fastForwardButton),e.cartButton&&(e.cartButton.style.display="flex"),e.subtitleContainer&&(e.subtitleContainer.style.display="block"),e.forwardRewindControlsWrapper&&(e.forwardRewindControlsWrapper.style.display="none")}function Pi(e){e.controlsContainer.hasChildNodes(e.mobileControls)&&e.controlsContainer.removeChild(e.mobileControls)}function Ba(e){e.controlsContainer.appendChild(e.mobileControls),e.mobileControls.appendChild(e.mobileControlButtonsBlock),e.mobileControlButtonsBlock.appendChild(e.rewindBackButton),e.mobileControlButtonsBlock.appendChild(e.fastForwardButton);let t=getComputedStyle(e.mobileControlButtonsBlock).getPropertyValue("--forward-skip-button").trim(),i=getComputedStyle(e.mobileControlButtonsBlock).getPropertyValue("--backward-skip-button").trim(),r=getComputedStyle(e.mobileControlButtonsBlock).getPropertyValue("--next-episode-button").trim(),a=getComputedStyle(e.mobileControlButtonsBlock).getPropertyValue("--previous-episode-button").trim();t==="none"&&e.mobileControls.classList.add("forwardSkipButtonHidden"),i==="none"&&e.mobileControls.classList.add("rewindBackButtonHidden"),r==="none"&&e.mobileControls.classList.add("nextButtonDisabledMobile"),a==="none"&&e.mobileControls.classList.add("prevButtonDisabledMobile"),e.controlsContainer.classList.contains("hasPlaylist")&&(e.mobileControlButtonsBlock.prepend(e.prevButton),e.mobileControlButtonsBlock.appendChild(e.nextButton))}function La(e){e.video.muted?e.volumeiOSButton.innerHTML=se:e.volumeiOSButton.innerHTML=ee,/iPad|iPhone|iPod/.test(navigator.userAgent)&&typeof window<"u"&&!window.MSStream?(e.parentVolumeDiv.classList.add("volumeControliOS"),e.volumeiOSButton.style.display="flex"):(e.parentVolumeDiv.classList.remove("volumeControliOS"),e.volumeControl.style.display="flex",e.volumeButton.style.display="flex",e.volumeiOSButton.style.display="none")}function _a(e){if(V()){let t=e.video;t.paused||t.pause()}}var ce=(e,t,i)=>{e.style.maxHeight=`${i-t}px`};function Aa(e){let t=e.video.offsetHeight;ce(e.resolutionMenu,59,t),ce(e.audioMenu,79,t),ce(e.subtitleMenu,79,t),ce(e.thumbnail,59,t)}function Pa(e){!e.controlsContainer.classList.contains("hasPlaylist")&&e.bottomRightDiv.contains(e.playlistButton)&&e.bottomRightDiv.removeChild(e.playlistButton)}function Ma(e,t,i,r,a){e.forwardRewindControlsWrapper.id="forwardRewindControlsWrapperMini",e.forwardRewindControlsWrapper.style.bottom="50%",e.forwardRewindControlsWrapper.style.display="none",e.forwardRewindControlsWrapper.style.opacity="0",e.mobileControls.style.display="flex",e.mobileControlButtonsBlock.style.display="flex",e.rewindBackButton.style.opacity=1,e.fastForwardButton.style.opacity=1,e.progressBar.id="progressBarMini",e.bottomRightDiv.id="bottomRightDivMini",e.parentVolumeDiv.id="parentVolumeMini",Sa(e),e.leftControls.classList.add("mobile"),e.playPauseButton.classList.add("mobile"),e.titleElement.classList.add("mobile"),e.bottomRightDiv.classList.add("mobile"),e.subtitleContainer.classList.add("mobile"),e.progressBarContainer.classList.add("mobile"),e.progressBar.classList.add("mobile"),e.parentVolumeDiv.classList.add("mobile"),e.playlistPanel&&ce(e.playlistPanel,20,t),e.subtitleContainer.classList.add("medium"),e.subtitleContainer.classList.remove("large"),e.progressBarContainer.classList.remove("mobile"),e.progressBar.classList.remove("mobile"),a.forEach(s=>s.classList.add("chapter-marker-mini")),a.forEach(s=>s.classList.remove("chapter-marker-md")),a.forEach(s=>s.classList.remove("chapter-marker-lg")),e.controlsContainer.classList.add("mobile")}function Da(e,t,i,r,a){e.forwardRewindControlsWrapper.id="forwardRewindControlsWrapperMini",e.forwardRewindControlsWrapper.style.bottom="50%",e.forwardRewindControlsWrapper.style.display="none",e.forwardRewindControlsWrapper.style.opacity="0",e.mobileControlButtonsBlock.style.display="flex",e.progressBar.id="progressBarMini",e.bottomRightDiv.id="bottomRightDivMini",e.parentVolumeDiv.id="parentVolumeMini",e.leftControls.appendChild(e.parentVolumeDiv),e.leftControls.classList.add("mobile"),e.playPauseButton.classList.add("mobile"),e.titleElement.classList.add("mobile"),e.bottomRightDiv.classList.add("mobile"),e.subtitleContainer.classList.add("mobile"),e.progressBarContainer.classList.add("mobile"),e.progressBar.classList.add("mobile"),e.parentVolumeDiv.classList.add("mobile"),e.playlistPanel&&ce(e.playlistPanel,20,t),Ea(e),e.subtitleContainer.classList.add("medium"),e.subtitleContainer.classList.remove("large"),e.progressBar.classList.remove("mobile"),a.forEach(s=>s.classList.add("chapter-marker-mini")),a.forEach(s=>s.classList.remove("chapter-marker-md")),a.forEach(s=>s.classList.remove("chapter-marker-lg")),e.controlsContainer.classList.add("mobile")}function Ia(e,t,i,r,a){e.progressBar.id="progressBarResponsive",e.bottomRightDiv.id="bottomRightDivResponsive",e.forwardRewindControlsWrapper.id="forwardRewindControlsWrapperResponsive",e.forwardRewindControlsWrapper.style.bottom="50%",e.mobileControlButtonsBlock.style.display="flex",e.timeDisplay.id="timeDisplayResponsive",e.parentVolumeDiv.id="parentVolumeResponsive",e.leftControls.appendChild(e.parentVolumeDiv),e.leftControls.appendChild(e.volumeiOSButton),Ta(e),e.playlistPanel&&ce(e.playlistPanel,79,t),e.leftControls.classList.add("mobile"),e.playPauseButton.classList.add("mobile"),e.titleElement.classList.add("mobile"),e.bottomRightDiv.classList.add("mobile"),e.subtitleContainer.classList.add("mobile"),e.progressBarContainer.classList.add("mobile"),e.progressBar.classList.add("mobile"),e.subtitleContainer.classList.remove("medium"),e.subtitleContainer.classList.remove("large"),e.progressBarContainer.classList.remove("medium"),a.forEach(s=>s.classList.add("chapter-marker-mini")),a.forEach(s=>s.classList.remove("chapter-marker-md")),a.forEach(s=>s.classList.remove("chapter-marker-lg")),e.controlsContainer.classList.add("mobile")}function Ha(e,t,i,r,a){e.ccButton.style.display!=="none"?e.playbackRateButton.classList.add("showPlaybackrateButton"):e.playbackRateButton.classList.remove("showPlaybackrateButton"),e.progressBar.id="progressBarResponsiveMd",e.bottomRightDiv.id="bottomRightDivMd",e.forwardRewindControlsWrapper.id="forwardRewindControlsWrapperMd",e.forwardRewindControlsWrapper.style.bottom="10px",e.parentVolumeDiv.id="parentVolumeResponsiveMd",e.playPauseButton.id="playPauseButtonMd",e.playlistPanel&&ce(e.playlistPanel,100,t),e.leftControls.prepend(e.forwardRewindControlsWrapper),e.controlsContainer.classList.contains("hasPlaylist")&&(e.forwardRewindControlsWrapper.prepend(e.nextButton),e.forwardRewindControlsWrapper.prepend(e.prevButton)),e.forwardRewindControlsWrapper.style.display="inline-flex",e.forwardRewindControlsWrapper.style.opacity="1",e.leftControls.appendChild(e.timeDisplay),e.timeDisplay.style.opacity="1",e.timeDisplay.style.display=Ce(e),e.forwardRewindControlsWrapper.appendChild(e.rewindBackButton),e.rewindBackButton.style.opacity=1,e.forwardRewindControlsWrapper.appendChild(e.fastForwardButton),e.fastForwardButton.style.opacity=1,e.leftControls.appendChild(e.parentVolumeDiv),e.leftControls.appendChild(e.volumeiOSButton),e.controlsContainer.hasChildNodes(e.mobileControls)&&e.controlsContainer.removeChild(e.mobileControls),e.subtitleContainer.classList.add("medium"),e.subtitleContainer.classList.remove("large"),e.leftControls.classList.remove("mobile"),e.playPauseButton.classList.remove("mobile"),e.titleElement.classList.remove("mobile"),e.bottomRightDiv.classList.remove("mobile"),e.subtitleContainer.classList.remove("mobile"),e.progressBarContainer.classList.remove("mobile"),e.progressBar.classList.remove("mobile"),e.parentVolumeDiv.classList.remove("mobile"),e.progressBarContainer.classList.add("medium"),e.progressBarContainer.classList.remove("large"),a.forEach(s=>s.classList.remove("chapter-marker-mini")),a.forEach(s=>s.classList.add("chapter-marker-md")),a.forEach(s=>s.classList.remove("chapter-marker-lg")),e.controlsContainer.classList.remove("mobile"),e.cartButton&&(e.cartButton.style.display="flex"),e.subtitleContainer&&(e.subtitleContainer.style.display="block"),e.forwardRewindControlsWrapper&&(e.forwardRewindControlsWrapper.style.display="inline-flex")}function Ra(e,t,i,r,a){e.progressBar.id="progressBarResponsiveHeightWidth",e.parentVolumeDiv.id="parentVolumeHeightWidth",e.bottomRightDiv.id="bottomRightDivHeightWidth",e.leftControls.classList.remove("mobile"),e.titleElement.classList.remove("mobile"),e.progressBarContainer.classList.remove("mobile"),e.progressBar.classList.remove("mobile"),e.subtitleContainer.classList.remove("large"),e.subtitleContainer.classList.add("medium"),e.leftControls.prepend(e.forwardRewindControlsWrapper),e.forwardRewindControlsWrapper.style.display="inline-flex",e.forwardRewindControlsWrapper.style.opacity="1",e.leftControls.appendChild(e.timeDisplay),e.timeDisplay.style.opacity="1",e.timeDisplay.style.display=Ce(e),e.controlsContainer.classList.contains("hasPlaylist")&&(e.leftControls.appendChild(e.prevButton),e.leftControls.appendChild(e.nextButton)),e.forwardRewindControlsWrapper.appendChild(e.rewindBackButton),e.rewindBackButton.style.opacity=1,e.forwardRewindControlsWrapper.appendChild(e.fastForwardButton),e.fastForwardButton.style.opacity=1,e.leftControls.appendChild(e.parentVolumeDiv),e.leftControls.appendChild(e.volumeiOSButton),Pi(e),e.leftControls.classList.remove("mobile"),e.playPauseButton.classList.remove("mobile"),e.titleElement.classList.remove("mobile"),e.bottomRightDiv.classList.remove("mobile"),e.wrapper.classList.remove("mobile"),e.progressBarContainer.classList.remove("mobile"),e.progressBar.classList.remove("mobile"),e.parentVolumeDiv.classList.remove("mobile"),a.forEach(s=>s.classList.remove("chapter-marker-mini")),a.forEach(s=>s.classList.remove("chapter-marker-md")),a.forEach(s=>s.classList.remove("chapter-marker-lg")),e.controlsContainer.classList.remove("mobile"),e.cartButton&&(e.cartButton.style.display="flex"),e.subtitleContainer&&(e.subtitleContainer.style.display="block"),e.forwardRewindControlsWrapper&&(e.forwardRewindControlsWrapper.style.display="inline-flex")}function Va(e){let t=e.leftControls,i=e.bottomRightDiv;if(!t||!i)return!1;let r=t.getBoundingClientRect(),a=i.getBoundingClientRect();return!(r.right<a.left||r.left>a.right||r.bottom<a.top||r.top>a.bottom)}function Oa(e){let t=e.leftControls,i=e.bottomRightDiv;if(!t||!i)return 0;let r=t.getBoundingClientRect(),a=i.getBoundingClientRect(),s=Math.max(r.left,a.left),o=Math.min(r.right,a.right);return Math.max(0,o-s)}function Fa(e){if(!e.isHotspotVisible)return;let t=e.wrapper?.querySelectorAll(".hotspot");!t||t.length===0||t.forEach(i=>{let r=i.dataset.xPercent||i.dataset.x,a=i.dataset.yPercent||i.dataset.y;r!==void 0&&a!==void 0&&e.positionHotspot(i,Number(r),Number(a))})}function Na(e,t){e.progressBar.id="progressBar",e.parentVolumeDiv.id="parentVolume",e.bottomRightDiv.id="bottomRightDiv",e.forwardRewindControlsWrapper.id="forwardRewindControlsWrapperLg",e.forwardRewindControlsWrapper.style.bottom="6px",e.leftControls.classList.remove("mobile"),e.progressBarContainer.classList.remove("mobile"),e.progressBar.classList.remove("mobile"),e.leftControls.prepend(e.forwardRewindControlsWrapper),e.forwardRewindControlsWrapper.style.display="inline-flex",e.forwardRewindControlsWrapper.style.opacity="1",e.leftControls.appendChild(e.timeDisplay),e.timeDisplay.style.opacity="1",e.timeDisplay.style.display=Ce(e),e.controlsContainer.classList.contains("hasPlaylist")&&(e.forwardRewindControlsWrapper.prepend(e.nextButton),e.forwardRewindControlsWrapper.prepend(e.prevButton),e.prevButton.id="prevButtonLg",e.nextButton.id="nextButtonLg",e.prevButton.classList.add("prevButtonLg"),e.nextButton.classList.add("nextButtonLg")),e.subtitleContainer.classList.remove("medium","mobile"),e.subtitleContainer.classList.add("large"),e.progressBarContainer.classList.remove("medium"),e.forwardRewindControlsWrapper.appendChild(e.rewindBackButton),e.rewindBackButton.style.opacity=1,e.forwardRewindControlsWrapper.appendChild(e.fastForwardButton),e.fastForwardButton.style.opacity=1,e.leftControls.appendChild(e.parentVolumeDiv),e.leftControls.appendChild(e.volumeiOSButton),Pi(e),[e.leftControls,e.playPauseButton,e.titleElement,e.bottomRightDiv,e.wrapper,e.parentVolumeDiv,e.controlsContainer].forEach(r=>r?.classList?.remove("mobile")),e.cartButton&&(e.cartButton.style.display="flex"),e.subtitleContainer&&(e.subtitleContainer.style.display="block"),e.forwardRewindControlsWrapper&&(e.forwardRewindControlsWrapper.style.display="inline-flex"),t.forEach(r=>{r.classList.remove("chapter-marker-mini","chapter-marker-md"),r.classList.add("chapter-marker-lg")})}function qa(e,t,i){if(t<600||t>800)return;let r=e.timeDisplay,a=e.volumeControl;i?(r&&(r.style.display="none"),a&&(a.style.display="none")):(r&&(r.style.display=Ce(e)),a&&(a.style.display=""))}function za(e,t,i){if(t<488||t>615)return;let r=e.timeDisplay,a=e.volumeControl;i?(r&&(r.style.display="none"),a&&(a.style.display="none")):(r&&(r.style.display=Ce(e)),a&&(a.style.display=""))}function Ua(e){return[e.audioButton,e.castButton,e.playlistButton,e.ccButton].filter(i=>i&&i.style.display!=="none").length}function Je(e,t,i,r){e.style.display=i?"":"none",t&&(t.style.display=r?"":"none")}function Wa(e,t,i,r){let a=Ua(e);a>=2?Je(t,i,!1,!1):a===0?Je(t,i,!0,!0):Je(t,i,!0,!1)}function ja(e,t){let i=e.pipButton,r=e.playbackRateButton;if(i){if(t<=471){i.style.display="none";return}t<=600?Wa(e,i,r,t):Je(i,r,!0,!0)}}function $a(e,t){t<485||t>510||(e.leftControls&&e.leftControls.classList.add("medium"),e.progressBarContainer&&e.progressBarContainer.classList.add("medium"),e.progressBar&&e.progressBar.classList.add("medium"),e.subtitleContainer&&e.subtitleContainer.classList.add("medium"),e.bottomRightDiv&&e.bottomRightDiv.classList.add("medium"),e.wrapper&&e.wrapper.classList.add("medium"))}function Za(e,t){t>=600&&t<=800||t>=488&&t<=615||(e.timeDisplay&&(e.timeDisplay.style.display=Ce(e)),e.volumeControl&&(e.volumeControl.style.display=""))}function Ka(e,t){if(t>471)return;e.progressBar?.classList?.contains("cartSidebarOpen-progress-bar")&&e.bottomRightDiv&&e.bottomRightDiv.classList.add("mobile")}function Ga(e,t){let i=!!e.isCartOpen;qa(e,t,i),za(e,t,i),Za(e,t),$a(e,t),Ka(e,t)}function Qa(e,t){let i=e.cartSidebar?.querySelector(".cartSidebarProducts");i&&(t<=471?i.classList.add("mobile"):i.classList.remove("mobile"))}function Ya(e,t){let i=e.forwardRewindControlsWrapper;if(i)if(t>=472)i.style.display="inline-flex",i.style.opacity="1";else{i.style.display="none",i.style.opacity="0";let r=i.parentElement;r&&r!==e.mobileControlButtonsBlock&&r.removeChild(i)}}function Xa(e,t){return e<150?{scalingFactor:.6,sizeClass:"sm",deviceType:"mini"}:e>=150&&e<=244?{scalingFactor:.6,sizeClass:"sm",deviceType:"smallMobile"}:e>=245&&e<=471?{scalingFactor:.6,sizeClass:"sm",deviceType:"responsive"}:e>=472&&e<=950?{scalingFactor:.6,sizeClass:"md",deviceType:"tablet"}:e<t?{scalingFactor:.6,sizeClass:"md",deviceType:"portrait"}:{scalingFactor:.6,sizeClass:"lg",deviceType:"large"}}function Ja(e,t,i,r){let{deviceType:a,scalingFactor:s,sizeClass:o}=t;switch(a){case"mini":Ma(e,i,s,o,r);break;case"smallMobile":Da(e,i,s,o,r);break;case"responsive":Ia(e,i,s,o,r);break;case"tablet":Ha(e,i,s,o,r);break;case"portrait":Ra(e,i,s,o,r);break;case"large":Na(e,r);break}}function K(e){_a(e);let t=e.video,i=t.offsetWidth,r=t.offsetHeight;Aa(e);let a=e.progressBarContainer.querySelectorAll(".chapter-marker");Ba(e),La(e),Pa(e);let s=Xa(i,r);if(Ja(e,s,r,a),e.playlistSlot){let u=e.playlistSlot;u.classList.remove("playlistSlot-sm","playlistSlot-md","playlistSlot-lg","device-mini","device-smallMobile","device-responsive","device-tablet","device-portrait","device-large"),u.classList.add(`playlistSlot-${s.sizeClass}`),u.classList.add(`device-${s.deviceType}`)}Ga(e,i),Qa(e,i),Ya(e,i),ja(e,i),e.thumbnail.style.setProperty("--scaling-factor",s.scalingFactor),e.thumbnail.classList.remove("lg","md","sm"),e.thumbnail.classList.add(s.sizeClass),Fa(e);let o=Va(e),l=Oa(e);o&&e.debugAttribute}function xa(e){let t=e.thumbnail.querySelector(".thumbnailTimeDisplay");for(;e.thumbnail.firstChild;)e.thumbnail.removeChild(e.thumbnail.firstChild);t&&e.thumbnail.appendChild(t)}function At(e,t,i){e.progressBar.addEventListener("mousemove",r=>{t(r.clientX)}),e.progressBar.addEventListener("mousedown",r=>{t(r.clientX)}),e.progressBar.addEventListener("click",r=>{t(r.clientX)}),e.progressBar.addEventListener("mouseleave",()=>{i.style.display="none",e.thumbnail.classList.remove("show")}),e.progressBar.addEventListener("touchmove",r=>{let a=r.touches[0];t(a.clientX)},{passive:!0}),e.progressBar.addEventListener("touchend",()=>{Ii(e)})}async function Mi(e,t,i){if(!t||!i)return null;if(e.spritesheetCache?.[t])return e.spritesheetCache[t];e.spritesheetCache??(e.spritesheetCache={});try{let r=`${i}/${t}/spritesheet.json`,a=e.token;a&&(r+=`?token=${a}`);let s=await fetch(r);if(!s.ok)return null;let o=await s.json();return e.spritesheetCache[t]=o,o}catch{return null}}function es(e){xa(e),e.thumbnailSeekingContainer.appendChild(e.thumbnail),e.controlsContainer.appendChild(e.thumbnailSeekingContainer),ts(e),is(e),rs(e)}function ts(e){let t=e.thumbnail.querySelector(".thumbnailTimeDisplay")??m.createElement("div");t.classList.contains("thumbnailTimeDisplay")||(t.className="thumbnailTimeDisplay",t.textContent="00:00",e.thumbnail.appendChild(t))}function is(e){let t=e.thumbnail.querySelector(".thumbnailSeekingArrow")??m.createElement("div");t.classList.contains("thumbnailSeekingArrow")||(t.className="thumbnailSeekingArrow",e.thumbnail.appendChild(t))}function rs(e){let t=e.controlsContainer.querySelector(".seekbarPin")??m.createElement("div");t.classList.contains("seekbarPin")||(t.className="seekbarPin",e.controlsContainer.appendChild(t))}function as(e,t){let i=parseFloat(getComputedStyle(e.thumbnail).getPropertyValue("--scaling-factor")),r=t?t.tile_width:0,a=t?t.tile_height:0;return{width:r*i,height:a*i,scalingFactor:i}}function ss(e,t,i,r,a,s,o){let l=s?i:e.thumbnail.offsetWidth||48,u=a+l/2,n=a+r-l/2,d=a+t,f;d<=u?f=a:d>=n?f=a+r-l:f=d-l/2;let p=e.thumbnail.offsetParent,C=p?o.left-p.getBoundingClientRect().left:0;e.thumbnail.style.left=`${f+C}px`,e.thumbnail.style.right="auto",e.thumbnail.style.transform="translateX(0)"}function os(e,t,i,r){let a=e.thumbnail.querySelector(".thumbnailSeekingArrow");t>=r-20?(a.style.left="auto",a.style.right=`${i/2}px`):(a.style.left=`${i/2}px`,a.style.right="auto")}function ns(e,t){let i="";for(let r of e.chapters)if(t>=r.startTime&&t<=r.endTime){i=r.value??"",e.currentChapter!==r&&(e.currentChapter=r);break}e.chapterDisplay.textContent=i,e.chapterDisplay.classList.add("multi-line"),e.thumbnail.appendChild(e.chapterDisplay)}function Di(e,t,i,r){return a=>{let s=e.progressBar.getBoundingClientRect(),o=a-s.left,u=o/s.width*e.video.duration;if(ls(u,e)){Ii(e);return}r&&(e.video.seeking||e.video.readyState<3)&&(u=e.video.currentTime),us(e,u,o,i,t,r),ns(e,u)}}function ls(e,t){return isNaN(e)||e<0||e>t.video.duration}function Ii(e){e.thumbnail.classList.remove("show");let t=e.controlsContainer.querySelector(".seekbarPin");t&&(t.style.display="none")}function us(e,t,i,r,a,s){let{width:o}=r,l=e.progressBar.getBoundingClientRect(),u=e.controlsContainer.getBoundingClientRect(),n=l.width,d=l.left-u.left,f=n-o/2-d;e.thumbnail.classList.add("show"),ds(e,t),ss(e,i,o,n,d,!!s,u),os(e,i,o,f),ps(e,t,a,s,r);let p=e.controlsContainer.querySelector(".seekbarPin");p&&(p.style.display="block",p.style.position="fixed",p.style.left=`${l.left+i}px`,p.style.top=`${l.top+l.height/2}px`,p.style.transform="translate(-50%, -50%)")}function ds(e,t){let i=e.thumbnail.querySelector(".thumbnailTimeDisplay"),r;t<=0?r="00:00":t>=e.video.duration?r=J(e.video.duration):r=J(t),i.innerHTML!==r&&(i.innerHTML=r)}function ps(e,t,i,r,a){if(!i||!r)return;let s=cs(i,t);if(s){let{scalingFactor:o}=a;e.thumbnail.style.backgroundImage=`url(${r})`,e.thumbnail.style.backgroundPosition=`-${s.x*o}px -${s.y*o}px`,e.thumbnail.style.backgroundSize=`${e.spritesheetImage.width*o}px ${e.spritesheetImage.height*o}px`}}function cs(e,t){for(let i=0;i<e.tiles.length-1;i++)if(e.tiles[i].start<=t&&e.tiles[i+1].start>t)return e.tiles[i];return null}async function Hi(e,t,i){let r=`spritesheetUrl-${t}-${i}`,a=sessionStorage.getItem(r),s;a?s=await Mi(e,t,a):(s=await Mi(e,t,i),s?.url&&sessionStorage.setItem(r,i));let o=s?.url??null;o===null?(e.thumbnail.classList.add("noThumbnail"),e.progressBar&&e.progressBar.setAttribute("title","")):e.thumbnail.classList.remove("noThumbnail"),es(e);let l=as(e,s),u=new Image;o&&(u.src=o,e.spritesheetImage=u);let n=Di(e,s,l,o);o?(u.onload=()=>{e.thumbnail.style.width=`${l.width}px`,e.thumbnail.style.height=`${l.height}px`,At(e,n,e.controlsContainer.querySelector(".seekbarPin"))},u.onerror=()=>{e.thumbnail.classList.add("noThumbnail"),e.thumbnail.style.width="",e.thumbnail.style.height="",e.progressBar&&e.progressBar.setAttribute("title","");let d=Di(e,s,l,null);At(e,d,e.controlsContainer.querySelector(".seekbarPin"))}):At(e,n,e.controlsContainer.querySelector(".seekbarPin"))}function Ri(e){e.placeholderAttribute&&(e.video.poster=e.placeholderAttribute);let t=e.thumbnailToken,i=e.hasAttribute("thumbnail-time"),r=e.playbackId,a=l=>{if(l==null)return"";let u=String(l).trim();return!u||u.toLowerCase()==="null"?"":u.replace(/\/+$/,"")},s=l=>{let u=a(l);if(!u||!r)return"";let n=`${u}/${r}/thumbnail.jpg`;return t&&(n+=`?token=${t}`),i&&(n+=`${t?"&":"?"}time=${e.thumbnailTimeAttribute}`),n},o=a(e.thumbnailUrlAttribute)||a(e.thumbnailUrlFinal);if(o&&r&&!e.posterAttribute){let l=s(o);if(l){let u=new Image;u.onload=()=>{e.posterAttribute||(e.video.poster=l)},u.src=l}}e.posterAttribute&&(e.video.poster=e.posterAttribute)}function oi(e){return getComputedStyle(e).getPropertyValue("--controls").trim()}function De(e){e.controlsContainer.style.opacity="0",e.playbackRateButton&&(e.playbackRateButton.style.opacity="0"),e.castButton&&(e.castButton.style.opacity="0"),e.playlistSlot&&(e.playlistSlot.style.opacity="0"),e.playbackRateDiv&&(e.playbackRateDiv.style.opacity="0"),e.volumeiOSButton.style.opacity="0",e.resolutionMenuButton.style.opacity="0",e.titleElement&&(e.titleElement.style.opacity="0"),e.subtitleContainer&&(e.subtitleContainer.style.opacity="0")}function we(e){e.controlsContainer.style.opacity="1",e.subtitleContainer&&(e.subtitleContainer.style.opacity="1")}function ms(e){e.controlsContainerValue!=="none"&&e.controlsContainer.style.setProperty("--controls","flex")}function Qe(e){e.controlsContainer.style.setProperty("--controls","none")}function je(e,t,i,r,a){e.controlsContainer.contains(e.mobileControlButtonsBlock)&&(e.mobileControlButtonsBlock.style.display="flex"),t>=471&&(e.playPauseButton.style.position="absolute",e.playPauseButton.id="playPauseAfterClickBreakPoint"),K(e),a==="on-demand"&&Hi(e,i,r??""),ms(e),j(e)}function Vi(e){e.videoOverLay.classList.add("overlay-show")}function M(e){let t=[e.playbackRateDiv,e.resolutionMenu,e.audioMenu,e.subtitleMenu,e.playlistPanel].filter(Boolean),i=!1;try{i=t.some(r=>r?.style?.display!=="none")}catch{}i&&t.forEach(r=>{try{r?.style&&(r.style.display="none")}catch{}});try{e.playlistPanel?.classList?.contains("open")&&(e.playlistPanel.classList.remove("open"),e.playlistPanel.classList.add("closing"),setTimeout(()=>{try{e.playlistPanel?.classList?.remove("closing"),e.playlistPanel?.style&&(e.playlistPanel.style.display="none")}catch{}},500))}catch{}}function Oi(e){e.videoOverLay.classList.remove("overlay-show")}function R(e){e.loader?.style.display!=="block"&&(e.loader.style.display="block",e.video?.offsetWidth<=471&&e.playPauseButton?.classList.remove("showPlayButton"))}function P(e){e.__fpAudioSwitchHoldActive||(e.loader.style.display="none",e.playPauseButton.classList.add("showPlayButton"))}function Fi(e){e.titleText&&(e.titleElement.textContent=e.titleText,e.streamType==="live-stream"?e.titleElement.className="title":e.titleElement.className="title-on-demand",e.parentLiveTitleContainer.appendChild(e.titleElement)),e.streamType==="live-stream"&&(e.liveStreamDisplay.textContent="LIVE",e.liveStreamDisplay.className="liveTag",e.fastForwardButton.style.display="none",e.rewindBackButton.style.display="none",e.playbackRateButton.style.display="none",e.progressBarContainer.style.display="none",e.hasAttribute("target-live-window")?e.bottomRightDiv.appendChild(e.playbackRateButton):e.bottomRightDiv.removeChild(e.playbackRateButton),e.timeDisplay.style.display="none",e.parentLiveTitleContainer.appendChild(e.liveStreamDisplay))}function L(e,t){if(e.suppressErrorUntilReady===!0||(e.isError=!0,e.wrapper.querySelector(".errorContainer")))return;let i=t.indexOf("."),r=`
        <div style="color: #F5F5F5; font-weight: bold; text-align: center; font-family: inherit;">
          ${t.substring(0,i+1)} 
        </div>
        <div style="color: #F5F5F5; text-align: center; margin-top: 10px; font-family: inherit;">
          ${t.substring(i+1).trim()}
        </div>
    `,a=m.createElement("div");a.classList.add("errorContainer"),a.style.position="absolute",a.style.top="50%",a.style.left="50%",a.style.transform="translate(-50%, -50%)",a.style.zIndex="9999",a.style.backgroundColor="rgba(0, 0, 0, 0.7)",a.style.width="100%",a.style.height="100%",a.style.display="flex",a.style.flexDirection="column",a.style.alignItems="center",a.style.justifyContent="center",a.innerHTML=r,e.wrapper.appendChild(a),typeof De=="function"&&De(e)}function Ie(e){let t=e.wrapper.querySelector(".errorContainer");e.isError=!1,t&&(e.wrapper.removeChild(t),typeof we=="function"&&we(e))}function hs(e){return typeof e.height=="number"&&e.height>e.width?e.width:e.height}function xe(e,t){let i=e.hls?.levels?.[t];if(!i)return null;let r=hs(i),a={id:t,label:`${r}p`,height:i.height,width:i.width};return typeof i.bitrate=="number"&&(a.bitrate=i.bitrate),typeof i.frameRate=="number"&&(a.frameRate=i.frameRate),a}function Ni(e){if(!e)return null;let t=e.loadLevel;if(typeof t=="number"&&t>=0)return t;let i=e.currentLevel;return typeof i=="number"&&i>=0?i:null}function qi(e){let t=e.userSelectedLevel==null?"auto":"manual",i=e.userSelectedLevel!=null?xe(e,e.userSelectedLevel):null,r=Ni(e.hls),a=r!=null?xe(e,r):null;return{mode:t,lockedLevel:i,loadedLevel:a}}function zi(e){return qi(e)}function Pt(e){let t=e.qualityLevelsOrdered;if(!Array.isArray(t)||!e.hls?.levels)return[];let i=[];for(let r of t){let a=e.hls.levels.indexOf(r);if(a<0)continue;let s=xe(e,a);s&&i.push(s)}return i}function et(e,t,i,r){try{e.dispatchEvent(new CustomEvent("fastpixqualityfailed",{detail:{reason:t,...i!==void 0?{levelId:i}:{},...r!==void 0?{raw:r}:{}}}))}catch{}}function ke(e){let t=qi(e),i=e._lastQualityEmitLoadedId,r=typeof i=="number"&&i>=0?xe(e,i):null;try{e.dispatchEvent(new CustomEvent("fastpixqualitychange",{detail:{mode:t.mode,lockedLevel:t.lockedLevel,loadedLevel:t.loadedLevel,previousLoadedLevel:r}}))}catch{}let a=Ni(e.hls);e._lastQualityEmitLoadedId=typeof a=="number"&&a>=0?a:null}function Ui(e){try{let t=Pt(e);e.dispatchEvent(new CustomEvent("fastpixqualitylevelsready",{detail:{levels:t}}))}catch{}}function fs(e,t){let i=e.qualityLevelsOrdered;if(!Array.isArray(i)||!e.hls?.levels)return t;let r=i[t];if(!r)return t;let a=e.hls.levels.indexOf(r);return a>=0?a:t}function Mt(e,t){if(!e.hls?.levels)return;e.resolutionSwitching=!0,e.wasPausedBeforeSwitch=e.video.paused,e.wasPausedBeforeSwitch||(e.video.pause(),R(e)),e.resolutionFlagPause=!0,e.isBufferFlushed=!1;let i=fs(e,t);e.hls.currentLevel=i,e.userSelectedLevel=i}function Wi(e,t){Array.from(t).forEach(i=>i.classList.remove("active")),e.classList.add("active")}function tt(e){e.hls&&(R(e),e.hls.nextLevel=-1,e.userSelectedLevel=null,e.autoResolutionButton&&e.resolutionButtons&&Wi(e.autoResolutionButton,[...e.resolutionButtons,e.autoResolutionButton]),ke(e))}function ji(e,t){if(!e.hls?.levels)return;let i=e.hls.levels.length;if(!Number.isFinite(t)||t<0||t>=i||Math.floor(t)!==t){et(e,"invalid levelId",t);return}let r=Array.isArray(e.qualityLevelsOrdered)?e.qualityLevelsOrdered.findIndex(a=>e.hls.levels.indexOf(a)===t):-1;if(r<0){et(e,"levelId not in manifest order",t);return}Mt(e,r),e.resolutionButtons?.[r]&&e.autoResolutionButton&&Wi(e.resolutionButtons[r],[...e.resolutionButtons,e.autoResolutionButton]),ke(e)}function D(){return ae()}function ie(e){requestAnimationFrame(()=>{requestAnimationFrame(e)})}function Ue(e){return e==="on-demand"}var Et={maxMaxBufferLength:120,autoStartLoad:!0,debug:!1,enableWorker:!1,startLevel:-1,backBufferLength:90,emeEnabled:!0,lowLatencyMode:!0,capLevelToPlayerSize:!0,abrMaxWithRealBitrate:!0,abrEwmaFastLive:2,abrEwmaSlowLive:8,abrEwmaFastVoD:3,abrEwmaSlowVoD:9,abrBandWidthUpFactor:.85,abrBandWidthFactor:.8,drmSystems:{"com.widevine.alpha":{robustness:"SW_SECURE_CRYPTO"},"com.apple.fps":{robustness:"SW_SECURE_CRYPTO"}}};async function ys(e){let t=e.config.drmSystems["com.apple.fps"];if(!t||!t.licenseUrl)return;let i=/^((?!chrome|android).)*safari/i.test(navigator.userAgent);try{try{let a=await(await navigator.requestMediaKeySystemAccess("com.apple.fps.1_0",[{initDataTypes:["cenc"],audioCapabilities:[{contentType:'audio/mp4;codecs="mp4a.40.2"',robustness:"SW_SECURE_CRYPTO"}],videoCapabilities:[{contentType:'video/mp4;codecs="avc1.42E01E"',robustness:"SW_SECURE_CRYPTO"}]}])).createMediaKeys();await e.video.setMediaKeys(a)}catch{try{let s=await(await navigator.requestMediaKeySystemAccess("com.widevine.alpha",[{initDataTypes:["cenc"],audioCapabilities:[{contentType:'audio/mp4;codecs="mp4a.40.2"',robustness:"SW_SECURE_CRYPTO"}],videoCapabilities:[{contentType:'video/mp4;codecs="avc1.42E01E"',robustness:"SW_SECURE_CRYPTO"}]}])).createMediaKeys();await e.video.setMediaKeys(s)}catch{}}}catch{}e.video.addEventListener("loadstart",()=>{}),e.video.addEventListener("loadedmetadata",()=>{}),e.video.addEventListener("canplay",()=>{}),e.video.addEventListener("webkitkeymessage",async r=>{try{if(r.messageType==="certificate-request"){let a=t.certificateUrl||t.serverCertificateUrl;if(a){let o=await(await fetch(a)).arrayBuffer(),l=new window.WebKitMediaKeyMessageEvent("webkitkeymessage",{message:o,messageType:"certificate"});e.video.dispatchEvent(l)}}else if(r.messageType==="license-request"){let a=r.message,s=t.licenseUrl,o=await fetch(s,{method:"POST",headers:{"Content-Type":"application/octet-stream"},body:a});if(!o.ok)throw new Error(`License request failed: ${o.status} ${o.statusText}`);let l=await o.arrayBuffer(),u=new window.WebKitMediaKeyMessageEvent("webkitkeymessage",{message:l,messageType:"license"});e.video.dispatchEvent(u)}}catch{}})}function at(e){let t=e?.__fpHlsNetworkListenersTeardown;if(typeof t=="function")try{t()}catch{}e.__fpHlsNetworkListenersTeardown=void 0}function bs(e,t){at(e);let i=!1,r=!0,a=!1,s=()=>{let p=e?.hls;if(!(!navigator.onLine||!r||!p))try{typeof p.startLoad=="function"&&p.startLoad()}catch{}},o=()=>{requestAnimationFrame(()=>s())},l=()=>{a?(L(e,"A fatal error occurred previously while loading a fragment. Please refresh the page to try again."),a=!1):(r=!0,Ie(e),i=!1,o())},u=()=>{e?.debugAttribute,!V()&&(L(e,"You are currently offline. Please connect to a network to continue watching."),r=!1)};function n(p,C){let S=D();if((C===S.ErrorDetails.LEVEL_LOAD_ERROR||C===S.ErrorDetails.LEVEL_EMPTY_ERROR||C===S.ErrorDetails.LEVEL_LOAD_TIMEOUT)&&et(p,String(C),void 0,C),C===D().ErrorDetails.KEY_SYSTEM_SESSION_UPDATE_FAILED){L(p,"A DRM (Digital Rights Management) error occurred. The playback session cannot continue due to a session update failure.");return}if(C===D().ErrorDetails.BUFFER_STALLED_ERROR){R(p);return}if(C.startsWith("key")){L(p,"A DRM (Digital Rights Management) error occurred. Please check your drm-token or token for the stream.");return}C===D().ErrorDetails.FRAG_LOAD_ERROR?(a=!0,L(p,"An error occurred while loading a fragment. Please try refreshing the page."),p.hls.destroy()):C===D().ErrorDetails.LEVEL_LOAD_ERROR||C===D().ErrorDetails.LEVEL_EMPTY_ERROR?L(p,"An Error occurred while loading the stream. Please try refreshing the page."):C===D().ErrorDetails.LEVEL_LOAD_TIMEOUT?(p.hls.destroy(),L(p,"An error occurred while loading the stream. Please try refreshing the page.")):C===D().ErrorDetails.AUDIO_TRACK_LOAD_TIMEOUT||C===D().ErrorDetails.MANIFEST_PARSING_ERROR?(L(p,"An error occurred while loading the video. Please try refreshing the page."),p.hls.destroy()):(L(p,"An error occurred while loading the video. Playback session cannot continue, try refreshing the page."),p.hls.destroy())}function d(p,C){C.startsWith("KEY_SYSTEM")&&(L(p,"A DRM error occurred, but the player is attempting to recover."),p.hls.recoverMediaError())}function f(p,C,S){S===D().ErrorTypes.MEDIA_ERROR&&(C===!0?L(p,"A problem occurred while buffering media. Playback cannot continue."):setTimeout(()=>requestAnimationFrame(()=>s()),1e3)),S===D().ErrorTypes.NETWORK_ERROR&&(!navigator.onLine&&!i?(L(p,"You are offline. Please connect to a network to continue watching."),i=!0):(s(),i=!1))}window.addEventListener("online",l),window.addEventListener("offline",u),e.__fpHlsNetworkListenersTeardown=()=>{window.removeEventListener("online",l),window.removeEventListener("offline",u)},e.hls.on(D().Events.ERROR,(p,C)=>{C.fatal?n(e,C.details):d(e,C.details),t==="on-demand"?gs(e,p):(vs(e,p),f(e,C.fatal,C.type))})}function gs(e,t){t.fatal&&(t.response&&t.response.code===404?L(e,"The video you are trying to access is not available."):t.response&&t.response.code===500&&L(e,"Server error while loading the video. Please try again later."))}function vs(e,t){t.fatal&&(t.response&&t.response.code===404&&t.details===D().ErrorDetails.MANIFEST_LOAD_ERROR?L(e,"No live stream is currently active on this channel."):t.response&&t.response.code===403&&L(e,"Invalid token. Please check your access rights."))}function Cs(){let e=navigator.connection;if(!e||e.saveData===!0)return 0;let t=(e.effectiveType||"").toLowerCase(),i=typeof e.downlink=="number"?e.downlink:10;return t==="slow-2g"||t==="2g"||t==="3g"&&i<1?0:-1}function vt(e,t,i){let r=e.enableCacheBusting?`${t}?t=${Date.now()}`:t;if(D().isSupported()){t&&typeof t=="string"&&(e.hls.attachMedia(e.video),e.video.loop=!!e.loopAttribute,e.hasAttribute("autoplay-shorts")&&(e.hls.startLevel=Cs()),e.hls.loadSource(r));let a=e.hasAttribute("auto-play")||e.hasAttribute("autoplay-shorts")||e.hasAttribute("loop-next");e.hls.on(D().Events.FRAG_LOADED,()=>{a||P(e)}),bs(e,i),e.hls.on(D().Events.FRAG_BUFFERED,()=>{a||P(e)})}else e.video.canPlayType("application/vnd.apple.mpegurl")?(e.debugAttribute,ys(e),e._src=r,e.video.src=r,e.video.loop=!!e.loopAttribute):L(e,"HLS is not supported, and the browser does not support the HLS format.")}function We(e){e.hls.on(D().Events.RECOVERED,()=>{Ie(e)}),e.hls.on(D().Events.MANIFEST_PARSED,()=>{e.hls.attachMedia(e.video)})}function ci(e){e.hls.on(D().Events.MANIFEST_PARSED,(t,i)=>{e._lastQualityEmitLoadedId=null;let r=i.levels,a=i.subtitleTracks;e.audioTracksRetrieved=i.audioTracks;let o=e.getAttribute("rendition-order")==="desc"?[...r].reverse():r;ws(e,o),Es(e,e.audioTracksRetrieved),Qi(e),As(e,a),Ps(e);try{let{audioTracks:l,currentAudioTrackId:u}=Ve(e),{subtitleTracks:n,currentSubtitleTrackId:d}=It(e),f=Array.isArray(l)?l.find(C=>C?.isCurrent)??null:null,p=Array.isArray(n)?n.find(C=>C?.isCurrent)??null:null;e.dispatchEvent(new CustomEvent("fastpixtracksready",{detail:{audioTracks:l,subtitleTracks:n,currentAudioId:u,currentSubtitleId:d,currentAudioTrackLoaded:f,currentSubtitleLoaded:p}}))}catch{}try{Ui(e),ke(e)}catch{}}),e.hls.on(D().Events.LEVEL_SWITCHED,()=>{ke(e)}),e.hls.on(D().Events.BUFFER_FLUSHED,()=>Ms(e))}function ws(e,t){if(e.qualityLevelsOrdered=Array.isArray(t)?t:[],e.resolutionMenu)for(;e.resolutionMenu.firstChild;)e.resolutionMenu.removeChild(e.resolutionMenu.firstChild);if(e.resolutionButtons=[],t.map(r=>r.height)[0]===0){e.bottomRightDiv.removeChild(e.resolutionMenuButton),e.bottomRightDiv.removeChild(e.pipButton);return}e.autoResolutionButton=$i("Auto",()=>{tt(e),Ke(e)}),e.resolutionMenu.appendChild(e.autoResolutionButton),e.resolutionButtons=t.map((r,a)=>{let s=typeof r.height=="number"&&r.height>r.width?r.width:r.height,o=$i(`${s}p`,()=>ks(e,a,s));return e.resolutionMenu.appendChild(o),o}),st(e.autoResolutionButton,[...e.resolutionButtons,e.autoResolutionButton])}function $i(e,t){let i=m.createElement("button");return i.className="qualitySelectorButtons",i.textContent=e,i.title=e,i.addEventListener("click",t),i}function ks(e,t,i){Mt(e,t),st(e.resolutionButtons[t],[...e.resolutionButtons,e.autoResolutionButton]),Ke(e),ke(e)}function Ss(e){return(e||"").toString().trim().toLowerCase()}function Gi(e){let t=[],i=new Map;for(let r of e){let a=Ss(r.label);if(!a){t.push(r);continue}let s=i.get(a);if(s===void 0){i.set(a,t.length),t.push(r);continue}!t[s].isCurrent&&r.isCurrent&&(t[s]=r)}return t}function Ve(e){let t=e.hls,i=Array.isArray(e.audioTracksRetrieved)?e.audioTracksRetrieved:Array.isArray(t?.audioTracks)?t.audioTracks:[],r=typeof t?.audioTrack=="number"&&t.audioTrack>=0?t.audioTrack:-1;if(r<0&&i.length>0){let u=e.getAttribute?.("default-audio-track");if(typeof u=="string"&&u.trim()){let n=u.trim().toLowerCase(),d=i.findIndex(f=>(f?.name??"").toString().trim().toLowerCase()===n);d>=0&&(r=d)}r<0&&(r=i.findIndex(n=>n?.default===!0)),r<0&&(r=i.findIndex(n=>(n?.name??"").toString().toLowerCase()==="default")),r<0&&(r=0)}let a=i.map((u,n)=>{let d=(u?.lang??"").toString().trim();return{id:n,label:(u?.name??"").toString().trim()||d||`Track ${n+1}`,language:d||void 0,isDefault:!!u?.default,isCurrent:n===r}}),s=Gi(a),o=a.find(u=>u.isCurrent),l=o?o.id:null;return{audioTracks:s,currentAudioTrackId:l}}function It(e){let t=e.video;if(!t||!t.textTracks)return{subtitleTracks:[],currentSubtitleTrackId:null};let a=Array.from(t.textTracks||[]).map((u,n)=>({track:u,index:n})).filter(({track:u})=>u.kind==="subtitles"||u.kind==="captions").map(({track:u,index:n})=>{let d=(u.language||"").toString().trim();return{id:n,label:(u.label||d||"").toString().trim()||`Track ${n+1}`,language:d||void 0,isDefault:u.mode==="showing",isCurrent:u.mode==="showing"}}),s=Gi(a),o=a.find(u=>u.isCurrent),l=o?o.id:null;return{subtitleTracks:s,currentSubtitleTrackId:l}}function Qi(e){let{audioTracks:t,currentAudioTrackId:i}=Ve(e);e.audioTracks=t,e.currentAudioTrackId=i}function Re(e){if(!e?.audioMenu)return;Qi(e);let{audioTracks:t}=Ve(e);e.audioMenu.innerHTML="";let i=(t||[]).map(a=>_s(e,a.label,a.id,!!a.isCurrent));e.audioMenu.append(...i);let r=(t||[]).findIndex(a=>a?.isCurrent);r>=0&&i[r]&&st(i[r],e.audioMenu.children),e.audioMenuButton.style.display=(t||[]).length>1?e.audioMenuButton.classList.add("audioMenuButtonShow"):e.audioMenuButton.classList.remove("audioMenuButtonShow")}function Es(e,t){let i=-1;if(Array.isArray(t)&&t.length>0){let r=e.getAttribute?.("default-audio-track");if(typeof r=="string"&&r.trim()){let a=r.trim().toLowerCase(),s=t.findIndex(o=>(o?.name??"").toString().trim().toLowerCase()===a);s>=0&&(i=s)}i===-1&&(i=t.findIndex(a=>a?.default===!0)),i===-1&&(i=t.findIndex(a=>(a?.name??"").toString().toLowerCase()==="default")),i===-1&&(i=0)}if(i>=0&&Array.isArray(t)&&t.length>0)try{e.hls.audioTrack=i,setTimeout(()=>{ie(()=>{try{e.hls?.audioTrack!==i&&(e.hls.audioTrack=i)}catch{}})},0)}catch{}Re(e)}function H(e,...t){try{e?.debugAttribute}catch{}}function it(e,t,i,r){H(e,"audio-switch loader display time (ms)",{ms:i,sessionId:t,reason:r})}function fe(e,t,i){let r=e?.__fpAudioSwitchT0;if(typeof r!="number")return;let a=Math.round((performance.now()-r)/10)/100;H(e,"audio-switch timing (s)",{phase:t,elapsedSec:a,...i??{}})}function Dt(e,t){let i=e?.__fpAudioSwitchT0;if(typeof i!="number")return;let r=Math.round((performance.now()-i)/10)/100,a=e.__fpAudioSwitchMeta;H(e,"audio-switch TOTAL duration (request \u2192 this point)",{totalSec:r,path:t,fromTrackIndex:a?.from,toTrackIndex:a?.to}),e.__fpAudioSwitchMeta=void 0}function He(e,t){let i=t??e?.video;!i||e?.__fpAudioSwitchUserHadPaused||i.play().catch(()=>{})}function Ts(e){clearTimeout(e.__fpAudioSwitchHideTimer),e.__fpAudioSwitchSession=(e.__fpAudioSwitchSession||0)+1;let t=e.__fpAudioSwitchSession;return R(e),e.__fpAudioSwitchLoaderShownAt=performance.now(),t}function Zi(e,t){t<0||(clearTimeout(e.__fpAudioSwitchHideTimer),e.__fpAudioSwitchHideTimer=setTimeout(()=>{ie(()=>{if(e.__fpAudioSwitchSession!==t)return;let i=e.__fpAudioSwitchLoaderShownAt;typeof i=="number"?(it(e,t,Math.round(performance.now()-i),"hide-after-switch"),e.__fpAudioSwitchLoaderShownAt=void 0):it(e,t,0,"hide-after-switch (no show timestamp)"),P(e),fe(e,"switch-complete (heavy: ui-loader-hidden)",{sessionId:t,note:"elapsed since switch-requested; includes 280ms post-SWITCHED debounce"});let r=e.__fpAudioSwitchOutcomePath??"heavy";e.__fpAudioSwitchOutcomePath=void 0,Dt(e,r==="heavy-fallback"?"heavy-fallback":"heavy"),e.__fpAudioSwitchT0=void 0,e.__fpAudioSwitchUserHadPaused=void 0})},280))}function rt(e,t){try{let i=e?.audioTracks,a=(Array.isArray(i)?i[t]:null)?.url;if(!a)return!1;let s=e?.loadLevelObj?.uri;return a!==s}catch{return!1}}function Ht(e,t,i){let r=e;return!r||i<0?!0:!(typeof t=="number"&&t>=0&&rt(r,t)&&!rt(r,i))}function Yi(e){let t=e?.video;if(!(!t||!Number.isFinite(t.currentTime)||t.paused)){try{let i=t.currentTime,r=t.duration,a=i+.001;t.currentTime=Number.isFinite(r)&&a>=r?Math.max(0,i-.001):a}catch{}requestAnimationFrame(()=>{t.play().catch(()=>{})})}}var Bs=380;function Ki(e){let t=e?.hls,i=e?.video;if(!(!t||!i||!Number.isFinite(i.currentTime)))try{typeof t.startLoad=="function"&&t.startLoad(i.currentTime,!0)}catch{}}function ye(e,t){let i=e?.hls,r=e?.video;if(!i||!r||!Number.isFinite(r.currentTime)){H(e,"directResume: skip (no hls/video/time)");return}let a=Date.now(),s=e.__fpDirectResumeAt??0;if(!t&&a-s<Bs){H(e,"directResume: throttled, video nudge only"),Yi(e);return}e.__fpDirectResumeAt=a;let o=(l,u)=>{if(!Number.isFinite(r.currentTime))return;let n=r.currentTime;if(H(e,`directResume: ${l}`,{t:n,muted:r.muted,paused:r.paused,hlsAudioTrack:i.audioTrack,force:!!t,withStartLoad:u}),u)try{typeof i.startLoad=="function"&&i.startLoad(n,!0)}catch(d){H(e,"directResume: startLoad error",d)}if(!r.paused){try{let d=r.duration,f=n+.001;r.currentTime=Number.isFinite(d)&&f>=d?Math.max(0,n-.001):f}catch{}r.play().catch(()=>{})}};queueMicrotask(()=>o("1",!0)),setTimeout(()=>ie(()=>o("2",!1)),t?90:55)}function Rt(e,t,i,r){let a=e?.video,s=t??e?.hls;if(!a||!Number.isFinite(a.currentTime)||!s||typeof s.on!="function"||typeof s.off!="function"||i<0){H(e,"nudge: skip register (missing deps or invalid id)");return}let l=typeof r=="number"&&r>=0&&rt(s,r)&&!rt(s,i),u=!l,n=T=>{if(H(e,`nudge wave (${T})`,{muted:a.muted,paused:a.paused}),a.paused){ye(e,!0);return}Yi(e)},d=e.__fpAudioTrackSwitchNudgeCleanup;if(typeof d=="function")try{d()}catch{}e.__fpAudioSwitchUserHadPaused=a.paused,e.__fpAudioSwitchHoldActive&&(e.__fpAudioSwitchHoldActive=!1,He(e,a));let f=u?-1:Ts(e);e.__fpAudioSwitchT0=performance.now(),e.__fpAudioSwitchSwitchingAt=void 0,e.__fpAudioSwitchMeta={from:r,to:i},H(e,"audio-switch timing (s)",{phase:"switch-requested",elapsedSec:0,from:r,to:i,lightweightAudioSwitch:u,altToMain:l});let p=!1,C=!1,S,w,g=(T,E)=>{if(p||C)return;let B=E?.id,O=s.audioTrack;if(!(B!=i&&O!=i)){if(C=!0,e.__fpAudioSwitchSwitchingAt=performance.now(),fe(e,"hls-AUDIO_TRACK_SWITCHING",{id:B,name:E?.name}),u){H(e,"AUDIO_TRACK_SWITCHING: lightweight \u2014 no early startLoad (hls default)");return}H(e,"AUDIO_TRACK_SWITCHING: early startLoad at playhead"),Ki(e)}},k=()=>{try{s.off(D().Events.BUFFER_FLUSHED,k)}catch{}p||s.audioTrack==i&&(H(e,"BUFFER_FLUSHED after alt\u2192main, nudge"),fe(e,"hls-BUFFER_FLUSHED (alt\u2192main only)",{altToMain:l}),l&&!a.paused&&!e.__fpAudioSwitchHoldActive&&(e.__fpAudioSwitchHoldActive=!0,e.__fpAudioSwitchHoldTime=a.currentTime,H(e,"hold: pause playhead while main audio buffer rebuilds (avoids silent skip)"),R(e),a.pause()),n("buffer-flushed"),queueMicrotask(()=>{Ki(e),ye(e,!0)}))},y=()=>{if(!p){p=!0,e.__fpAudioSwitchSwitchingAt=void 0;try{s.off(D().Events.AUDIO_TRACK_SWITCHING,g)}catch{}try{s.off(D().Events.AUDIO_TRACK_SWITCHED,v)}catch{}try{s.off(D().Events.BUFFER_FLUSHED,k)}catch{}S!==void 0&&clearTimeout(S),w!==void 0&&clearTimeout(w),e.__fpAudioTrackSwitchNudgeCleanup===h&&(e.__fpAudioTrackSwitchNudgeCleanup=void 0)}},h=()=>{y()},v=(T,E)=>{let B=E?.id,O=s.audioTrack;if(B!=i&&O!=i){H(e,"AUDIO_TRACK_SWITCHED ignored",{evId:B,hlsAt:O,expected:i,name:E?.name});return}H(e,"AUDIO_TRACK_SWITCHED matched",{evId:B,hlsAt:O,expected:i,muted:a.muted}),fe(e,"hls-AUDIO_TRACK_SWITCHED",{id:B,name:E?.name});let N=e.__fpAudioSwitchSwitchingAt;if(typeof N=="number"){let q=Math.round((performance.now()-N)/10)/100;H(e,"audio-switch HLS window (SWITCHING\u2192SWITCHED, mux-equivalent)",{engineWindowSec:q,id:B,name:E?.name})}if(y(),Re(e),e.__fpAudioSwitchHoldActive){e.__fpAudioSwitchHoldActive=!1;let q=e.__fpAudioSwitchHoldTime;if(typeof q=="number"&&Number.isFinite(q))try{a.currentTime=q}catch{}H(e,"hold: resume after audio track ready")}if(u){H(e,"AUDIO_TRACK_SWITCHED: lightweight path (no loader / no directResume)"),it(e,null,0,"lightweight-no-loader"),fe(e,"switch-complete (lightweight total)",{note:"no blocking loader"}),Dt(e,"lightweight"),e.__fpAudioSwitchT0=void 0,queueMicrotask(()=>{He(e,a),e.__fpAudioSwitchUserHadPaused=void 0});return}n("switched"),queueMicrotask(()=>{e.__fpAudioSwitchOutcomePath="heavy",ye(e,!0),Zi(e,f),He(e,a),e.__fpAudioSwitchUserHadPaused=void 0})};s.on(D().Events.AUDIO_TRACK_SWITCHING,g),s.on(D().Events.AUDIO_TRACK_SWITCHED,v),e.__fpAudioTrackSwitchNudgeCleanup=h,H(e,"listening for AUDIO_TRACK_SWITCHING / SWITCHED",{expected:i,previous:r,altToMain:l,lightweightAudioSwitch:u,muted:a.muted,paused:a.paused}),l&&s.on(D().Events.BUFFER_FLUSHED,k),u||(S=setTimeout(()=>{ie(()=>{p||s.audioTrack==i&&(H(e,"nudge mid-fallback @650ms (still listening for SWITCHED)"),n("fallback-mid"))})},650)),w=setTimeout(()=>{ie(()=>{if(p)return;let T=s.audioTrack==i;if(H(e,u?"lightweight audio switch final cleanup @3s":"nudge final-fallback @4s (stop listening)",{ok:T}),y(),Re(e),e.__fpAudioSwitchHoldActive){e.__fpAudioSwitchHoldActive=!1;let E=e.__fpAudioSwitchHoldTime;if(typeof E=="number"&&Number.isFinite(E))try{a.currentTime=E}catch{}H(e,"hold: resume (final fallback)")}if(u){it(e,null,0,"lightweight-fallback-timer-no-loader"),fe(e,"fallback-3s (!SWITCHED) lightweight",{ok:T}),Dt(e,"lightweight-fallback"),e.__fpAudioSwitchT0=void 0,He(e,a),e.__fpAudioSwitchUserHadPaused=void 0;return}fe(e,"fallback-4s (!SWITCHED yet) heavy path \u2192 schedule loader hide",{ok:T}),T&&n("fallback-final"),ye(e,!0),e.__fpAudioSwitchOutcomePath="heavy-fallback",Zi(e,f),He(e,a),e.__fpAudioSwitchUserHadPaused=void 0})},u?3e3:4e3)}function Ls(e){try{let t=typeof e.getAudioTracks=="function"?e.getAudioTracks():[],i=e.currentAudioTrackId!==void 0?e.currentAudioTrackId:null,r=Array.isArray(t)?t.find(a=>a?.isCurrent)??null:null;e.dispatchEvent(new CustomEvent("fastpixaudiochange",{detail:{tracks:t,currentId:i,currentTrack:r}}))}catch{}}function _s(e,t,i,r){let a=m.createElement("button");a.className="audioSelectorButtons";let s=(t??"").toString().toLowerCase()==="default"?"Default":(t??"").toString();return a.textContent=s,a.title=s,r&&a.classList.add("active"),a.addEventListener("click",o=>{let l=i;H(e,"audio UI: switch request",{to:l,from:e.hls?.audioTrack,muted:e.video?.muted,paused:e.video?.paused});let u=typeof e.hls?.audioTrack=="number"?e.hls.audioTrack:-1;Rt(e,e.hls,l,u),e.hls.audioTrack=l,Ht(e.hls,u,l)||ye(e,!0),setTimeout(()=>{ie(()=>{try{e.hls?.audioTrack!==l&&(H(e,"audio UI: re-apply track index"),e.hls.audioTrack=l)}catch{}})},0),st(a,e.audioMenu.children),Ze(e),Ls(e),o.stopPropagation()}),a}function As(e,t){e.ccButton.style.display=t.length>0?e.ccButton.classList.add("ccButtonLength"):e.ccButton.classList.remove("ccButtonLength")}function Ps(e){let t=e.resolutionMenuButton,i=t.cloneNode(!0);t.parentNode.replaceChild(i,t),e.resolutionMenuButton=i,e.resolutionMenuButton.addEventListener("click",()=>{if(e.resolutionMenu&&e.resolutionMenu.style.display!=="none"){e.resolutionMenu.style.display="none";return}M(e),Ke(e)})}function Ms(e){if(!e.resolutionSwitching||!e.initialPlayClick)return;if(e.isBufferFlushed){e.resolutionSwitching=!1;return}let t=e.video.currentTime;e.video.currentTime=t+.001,e.wasPausedBeforeSwitch?(P(e),e.resolutionSwitching=!1):e.video.play().then(()=>{e.isBufferFlushed=!0,P(e),e.resolutionSwitching=!1}).catch(i=>{let r=i?.name??"";P(e),e.isBufferFlushed=!0,e.resolutionSwitching=!1})}function st(e,t){Array.from(t).forEach(i=>i.classList.remove("active")),e.classList.add("active")}async function Xi(e){if(await ht(),e.hls)return;let t=ae();e.config={...e.config,startFragPrefetch:Ue(e.streamType)},e.hls=new t(e.config),We(e),ci(e)}function G(e){e.primaryColor=e.getAttribute("primary-color")??"#F5F5F5";let t=e.volumeControl.value,i=`linear-gradient(to right, ${e.primaryColor} 0%, ${e.primaryColor} ${(t*100).toFixed(2)}%, rgba(255, 255, 255, 0.1) ${(t*100).toFixed(2)}%, rgba(255, 255, 255, 0.1) 100%)`;e.volumeControl.style.background=i}function ot(e){e.video.muted?e.volumeiOSButton.innerHTML=se:e.volumeiOSButton.innerHTML=ee}function Q(e){let t=parseFloat(e.volumeControl.value);e.hasAttribute("no-volume-pref")?localStorage.removeItem("savedVolumeIcon"):localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML),e.video.muted?e.volumeButton.innerHTML=se:t===0?e.volumeButton.innerHTML=se:t>=.1&&t<=.6?e.volumeButton.innerHTML=Qt:e.volumeButton.innerHTML=ee}function Ji(e){e.isiOS=/iPad|iPhone|iPod/.test(navigator.userAgent)&&!window.MSStream,e.video.setAttribute("playsinline",""),e.video.removeAttribute("controls"),we(e),e.fullScreenButton.addEventListener("click",()=>{e.video.webkitDisplayingFullscreen?(e.video.setAttribute("controls","true"),De(e)):(e.video.removeAttribute("controls"),we(e)),e.video.webkitEnterFullscreen&&e.video.webkitEnterFullscreen()});let t=e.hasAttribute("no-volume-pref");localStorage.getItem("savedVolume")==="0"&&(e.video.muted=!0,ot(e)),e.volumeiOSButton.addEventListener("click",()=>{Ot(e,t),M(e)})}function Vt(e,t,i){let r=Math.min(1,Math.max(0,e.video.volume+t));e.video.volume=r,e.video.muted&&r>0&&(e.video.muted=!1),e.volumeControl.value=r.toString(),G(e),Q(e),i?(localStorage.removeItem("savedVolumeIcon"),localStorage.removeItem("savedVolume")):(localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML),localStorage.setItem("savedVolume",r.toString())),V()&&Z(e.video.volume,e.video.muted)}function xi(e){let t=localStorage.getItem("savedVolume");if(t!==null){e.primaryColor=e.getAttribute("primary-color")??"#F5F5F5",e.video.volume=parseFloat(t),e.volumeControl.value=t;let i=`linear-gradient(to right, ${e.primaryColor} 0%, ${e.primaryColor} ${(t*100).toFixed(2)}%, rgba(255, 255, 255, 0.1) ${(t*100).toFixed(2)}%, rgba(255, 255, 255, 0.1) 100%)`;e.volumeControl.style.background=i}}function Ot(e,t){t?localStorage.removeItem("savedVolumeIcon"):localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML);let i=localStorage.getItem("savedVolume");e.video.muted??i==="0"?(e.video.muted=!1,e.volumeButton.innerHTML=se,e.volumeControl.value="1",e.video.volume=1):(e.video.muted=!0,e.volumeButton.innerHTML=ee,e.volumeControl.value="0",e.video.volume=0),G(e),Q(e),ot(e),t?(localStorage.removeItem("savedVolume"),localStorage.removeItem("savedVolumeIcon")):(localStorage.setItem("savedVolume",e.video.muted?"0":e.video.volume.toString()),localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML))}function ir(e){return e&&typeof e.seekTime=="number"&&typeof e.x=="number"&&typeof e.y=="number"&&typeof e.tooltipPosition=="string"&&typeof e.link=="string"}function Ds(e,t,i){if(t.onProductClick?.type!=="openLink")return!1;t.onProductClick.shouldPause&&e.video.pause();let r=t.onProductClick.params?.targetUrl;return r&&window.open(r,"_blank","noopener,noreferrer"),(i===void 0||!i)&&e.triggerCartIconDance(),!0}function rr(e,t,i,r="1200"){let a=m.createElement("div");a.className="hotspot",a.style.position="absolute",a.style.width="32px",a.style.height="32px",a.style.cursor="pointer",a.style.zIndex=r,a.dataset.xPercent=String(t.x),a.dataset.yPercent=String(t.y),e.positionHotspot(a,Number(t.x),Number(t.y));let s=m.createElement("div");s.className="hotspot-dot",a.appendChild(s);let o=Is(t,i);return a.appendChild(o),a.onmouseenter=()=>o.style.opacity="1",a.onmouseleave=()=>o.style.opacity="0",a}function Is(e,t){let i=m.createElement("div");return i.className="hotspot-tooltip",i.innerText=String(t??"").replace(/\s+/g," ").trim(),i.style.position="absolute",i.style.whiteSpace="nowrap",i.style.background="#222",i.style.color="#fff",i.style.padding="6px 12px",i.style.borderRadius="6px",i.style.fontSize="0.95em",i.style.pointerEvents="none",i.style.opacity="0",i.style.transition="opacity 0.2s",Hs(i,e?.tooltipPosition),i}function Hs(e,t="bottom"){switch(t){case"left":e.style.right="110%",e.style.top="50%",e.style.transform="translateY(-50%)";break;case"right":e.style.left="110%",e.style.top="50%",e.style.transform="translateY(-50%)";break;case"top":e.style.left="50%",e.style.bottom="110%",e.style.transform="translateX(-50%)";break;default:e.style.left="50%",e.style.top="110%",e.style.transform="translateX(-50%)";break}}function ar(e,t){e.onclick=i=>{(i.target===e||e.contains(i.target))&&(i.stopPropagation(),window.open(t.link,"_blank","noopener,noreferrer"))}}function Rs(e,t){if(ir(t))return t.seekTime;if(e.onProductClick?.params?.seekTime&&typeof e.onProductClick.params.seekTime=="number")return e.onProductClick.params.seekTime}function Vs(e,t){if(t.onProductClick?.type!=="seek")return!1;let i=t?.markers[0],r=Rs(t,i);if(typeof r!="number"||!i)return!1;e.video.currentTime=r,e.video.pause(),e.removeAllHotspots();let a=rr(e,i,t.name,"1200");ar(a,i),e.wrapper.appendChild(a),e.isHotspotVisible=!0;let s=Number(t.onProductClick?.waitTillPause);return lr(e,a,s),!0}function Os(e,t){if(!t.markers?.length)return!1;let i=t.markers[0];if(!ir(i))return!1;e.video.currentTime=i.seekTime,e.video.pause(),e.removeAllHotspots();let r=rr(e,i,t.name,"1");ar(r,i),e.wrapper.appendChild(r),e.isHotspotVisible=!0;let a=Number(t.onProductClick?.waitTillPause);return lr(e,r,a),!0}function sr(e,t,i){if(t.onProductHover?.type!=="overlay")return;let r=m.createElement("div"),a=e.querySelector(".thumbWrap"),s=a||e,o=!!a;r.className=`product-hover-overlay${o?" post-play":""}`,r.style.position="absolute",r.style.background="rgba(34,34,34,0.85)",r.style.color="#fff",r.style.display="flex",r.style.alignItems="center",r.style.justifyContent="center",r.style.textAlign="center",r.style.fontSize="1em",r.style.boxSizing="border-box",r.style.zIndex="10",r.style.pointerEvents="none",r.style.opacity="0",r.style.transition="opacity 0.2s",r.innerText=t.onProductHover.params.description||"",s.appendChild(r),e.onmouseenter=()=>{r.style.opacity="1",i.dispatchEvent(new CustomEvent("productHoverPost",{detail:{product:t}}))},e.onmouseleave=()=>{r.style.opacity="0"}}function or(e,t,i){if(t.onProductHover?.type!=="swap")return;let r=e.querySelector("img");if(!r)return;let a=String(t.thumbnail),s=String(t.onProductHover?.params?.switchImage??t.thumbnail);try{let o=new Image;o.src=s}catch{}e.onmouseenter=()=>{r&&(r.src=s),i.dispatchEvent(new CustomEvent("productHover",{detail:{product:t}}))},e.onmouseleave=()=>{r&&(r.src=a)}}function nr(e,t,i){e.onclick=r=>{r.stopPropagation(),i.dispatchEvent(new CustomEvent("productClick",{detail:{product:t}})),Ft(i),i.closeCartSidebar(),!Ds(i,t)&&(Vs(i,t)||Os(i,t))}}function Ft(e){let t=e.wrapper.querySelector(".post-play-overlay");t&&t.remove(),e.controlsContainer&&(e.controlsContainer.style.display="")}function lr(e,t,i){i<=0||(e.playPauseButton.disabled=!1,e.hotspotPauseTimeout=setTimeout(()=>{e.wrapper.contains(t)&&(e.video.play(),e.removeAllHotspots())},i*1e3))}function Fs(e){if(!(e.getAttribute&&e.getAttribute("theme")==="shoppable-video-player"))return;let t=e.wrapper.querySelector(".post-play-overlay");t&&t.remove(),e.controlsContainer&&(e.controlsContainer.style.display="none");let i=m.createElement("div");i.className="post-play-overlay",i.style.position="absolute",i.style.top="0",i.style.left="0",i.style.width="100%",i.style.height="100%",i.style.display="flex",i.style.alignItems="center",i.style.justifyContent="center",i.style.zIndex="2000",i.style.backdropFilter="blur(8px)",i.style.background="rgba(0,0,0,0.35)",i.style.overflow="hidden";let r=m.createElement("div");r.className="post-play-products-row",r.style.display="flex",r.style.flexDirection="row",r.style.gap="16px",r.style.flexWrap="wrap",r.style.alignItems="center",r.style.justifyContent="center",r.style.alignContent="flex-start",r.style.boxSizing="border-box",r.style.padding="8px",r.style.maxHeight="calc(100% - 96px)",r.style.overflowY="auto";let a=e.wrapper.clientWidth||e.wrapper.offsetWidth||0,s=16,o=2;a>360&&(o=3),a>600&&(o=4),a>1e3&&(o=5);let l=Math.max(100,Math.floor((a-(o-1)*s)/o)),u=Math.max(80,Math.floor(l*.72));e.cartData.products.forEach(f=>{let p=m.createElement("div");p.className="cartProduct",p.style.display="flex",p.style.flexDirection="column",p.style.alignItems="center",p.style.justifyContent="flex-start",p.style.flex=`0 1 ${l}px`,p.style.width=`${l}px`,p.style.minWidth="0",p.style.boxSizing="border-box",p.style.padding="0",p.innerHTML=`
      <div class="thumbWrap" style="position:relative;width:100%;height:${u}px;overflow:hidden;border-radius:8px 8px 0 0;">
        <img src="${f.thumbnail}" class="cartProductImage" alt="${f.name}" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:8px 8px 0 0;"/>
      </div>
      <div style="margin-top:8px;font-weight:600;color:#222;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;box-sizing:border-box;padding:0 8px 8px;font-size:14px">${f.name}</div>
    `,nr(p,f,e),sr(p,f,e),or(p,f,e),r.appendChild(p)});let n=m.createElement("button");n.innerText="Replay",n.style.marginTop="16px",n.style.marginBottom="4px",n.style.padding="12px 32px",n.style.fontSize="1.1em",n.style.borderRadius="8px",n.style.border="none",n.style.background="var(--accent-color)",n.style.color="var(--primary-color)",n.style.cursor="pointer",n.style.boxShadow="0 2px 8px rgba(0,0,0,0.10)",n.style.transition="background 0.2s",n.onmouseenter=()=>{n.style.background="var(--primary-color)",n.style.color="var(--accent-color)"},n.onmouseleave=()=>{n.style.background="var(--accent-color)",n.style.color="var(--primary-color)"},n.onclick=()=>{e.hasAutoClosedSidebar=!1,e.video.currentTime=0,e.video.play(),i.remove(),e.controlsContainer&&(e.controlsContainer.style.display=""),e.dispatchEvent(new CustomEvent("replay"))};let d=m.createElement("div");d.style.display="flex",d.style.flexDirection="column",d.style.alignItems="center",d.style.maxHeight="100%",d.style.overflow="auto",d.appendChild(r),d.appendChild(n),i.appendChild(d),e.wrapper.appendChild(i)}function Ns(e){e.cartSidebar||(e.cartSidebar=m.createElement("div")),e.cartSidebar.className="cartSidebar",e.cartSidebar.style.cssText=`
    position: absolute;
    top: 0; right: 0; height: 100%;
    width: 0; background: var(--shoppable-sidebar-background-color); box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
    z-index: 1300; overflow: hidden; transition: width 0.2s ease;backdrop-filter: blur(4px);
    display: flex; flex-direction: column; align-items: stretch;
  `,e.cartSidebar.innerHTML=`
    <div class="cartSidebarProducts" style="flex:1;overflow-y:auto;padding:0 16px;"></div>
  `,e.wrapper.contains(e.cartSidebar)||e.wrapper.appendChild(e.cartSidebar),e.isSidebarHovered=!1,e.cartSidebar.addEventListener("mouseenter",()=>{e.isSidebarHovered=!0}),e.cartSidebar.addEventListener("mouseleave",()=>{e.isSidebarHovered=!1}),e.cartGotoLink=e.getAttribute("product-link")||void 0}function er(e){e.cartButton.onclick=t=>{t.stopPropagation();let i=e.getAttribute?e.getAttribute("theme"):null;if(i==="shoppable-shorts"){let r=e.cartGotoLink||"https://www.fastpix.io";window.open(r,"_blank","noopener,noreferrer");return}i==="shoppable-video-player"&&(e.isCartOpen?e.closeCartSidebar():e.openCartSidebar())}}function nt(e){let t=e.cartSidebar?.querySelector(".cartSidebarProducts");t&&(t.innerHTML="",e.cartData.products.forEach(i=>{let r=m.createElement("div");r.className="cartProduct",r.style.cssText=`
      display:flex;
      padding: 10px;
      margin-bottom:16px;
      cursor:pointer;
      align-items:center;
      justify-content:center;
      position: relative;
    `,r.innerHTML=`
      <img src="${i.thumbnail}" class="cartProductImage" alt="${i.name}" style="width:100%;height:auto;object-fit:cover;border-radius:8px;"/>
    `,typeof i.startTime=="number"&&(r.dataset.startTime=String(i.startTime)),typeof i.endTime=="number"&&(r.dataset.endTime=String(i.endTime)),sr(r,i,e),or(r,i,e),nr(r,i,e),t.appendChild(r)}))}function tr(e){let t=e.getAttribute("theme")==="shoppable-video-player",i=e.cartData.productSidebarConfig?.startState==="openOnPlay";if(t&&i){let s=()=>{e._openOnPlayDone||(e.hasAutoClosedSidebar||e.openCartSidebar(),e._openOnPlayDone=!0)};if(e.video&&!e.video.paused&&e.video.readyState>=2)setTimeout(s,0);else{e.video.addEventListener("playing",s,{once:!0}),e.video.addEventListener("play",s,{once:!0});let o=()=>{(e.video?.currentTime||0)>0&&(s(),e.video.removeEventListener("timeupdate",o))};e.video.addEventListener("timeupdate",o)}}if(!t)return;try{e._openCloseTUHandler&&e.video.removeEventListener("timeupdate",e._openCloseTUHandler)}catch{}let r=typeof e.cartData.productSidebarConfig?.autoClose=="number"?Number(e.cartData.productSidebarConfig.autoClose):null,a=()=>{zs(e),r!==null&&e.isCartOpen&&!e.hasAutoClosedSidebar&&(e.video?.currentTime??0)>=r&&!e.isSidebarHovered&&(e.closeCartSidebar(),e.hasAutoClosedSidebar=!0)};e._openCloseTUHandler=a,e.video.addEventListener("timeupdate",a)}function qs(e){e.video.addEventListener("ended",()=>{e.showPostPlayOverlay&&Fs(e)})}function lt(e){if(e._initShoppableRequested)return;e._initShoppableRequested=!0;let t=e.getAttribute?e.getAttribute("theme"):null;if(!(t!=="shoppable-video-player"&&t!=="shoppable-shorts"))if(e.wrapper.contains(e.cartButton)||e.wrapper.appendChild(e.cartButton),e.cartButton&&(e.cartButton.style.display="flex",e.cartButton.style.position="absolute",e.cartButton.style.top="16px",e.cartButton.style.right="16px",e.cartButton.style.zIndex="1600",e.cartButton.style.background="#fff",e.cartButton.style.borderRadius="50%",e.cartButton.style.boxShadow="0 2px 8px rgba(0,0,0,0.10)",e.cartButton.style.width="40px",e.cartButton.style.height="40px",e.cartButton.style.alignItems="center",e.cartButton.style.justifyContent="center",e.cartButton.style.border="none",e.cartButton.style.cursor="pointer",e.cartButton.style.opacity="0.6",t==="shoppable-shorts"&&(e.cartButton.style.visibility="visible",e.cartButton.style.opacity="1")),t==="shoppable-video-player"){Ns(e),er(e),nt(e),tr(e),qs(e);try{e.addEventListener("shoppabledatachange",()=>{try{e._openOnPlayDone=!1,e.hasAutoClosedSidebar=!1}catch{}tr(e),e.cartSidebar&&nt(e)})}catch{}}else t==="shoppable-shorts"&&er(e)}function zs(e){let t=e.cartSidebar?.querySelector(".cartSidebarProducts");if(!t)return;let i=e.video?.currentTime??0,r=null;if(Array.from(t.querySelectorAll(".cartProduct")).forEach(a=>{let s=Number(a.dataset?.startTime??NaN),o=Number(a.dataset?.endTime??NaN),l=!Number.isNaN(s)&&!Number.isNaN(o)&&i>=s&&i<=o,u=a.querySelector("img");l?(u&&(u.style.boxShadow="0 0 12px var(--accent-color)",u.style.border="2px solid var(--accent-color)",u.style.borderRadius="8px"),r??(r=a)):u&&(u.style.boxShadow="",u.style.border="")}),r&&e._lastActiveProductEl!==r){try{r.scrollIntoView({behavior:"smooth",block:"nearest"})}catch{}e._lastActiveProductEl=r}}var ur=e=>{if(e){let w=function(){let c=e.video,b=c.duration;if(!b||!isFinite(b))return;let _=_t(e),I=c.buffered.length>0?c.buffered.end(c.buffered.length-1):0,F=Math.min(_/b*100,100),he=Math.min(I/b*100,100),be=S();e.progressBar.style.background=`linear-gradient(to right, ${e.accentColor} 0%, ${e.accentColor} ${F}%, ${e.primaryColor} ${F}%, ${e.primaryColor} ${he}%, ${be} ${he}%, ${be} 100%)`,e.progressBar.value=F,e.progressBar.style.setProperty("--progressBar-thumb-position",`${F}%`)},k=function(){if(g!==null)return;function c(){w(),g=requestAnimationFrame(c)}g=requestAnimationFrame(c)},y=function(){g!==null&&(cancelAnimationFrame(g),g=null),w()},v=function(c){return c.hasAttribute("disable-keyboard-controls")&&c.getAttribute("disable-keyboard-controls")!=="false"},T=function(c,b){let _=c==="ArrowRight"?E(b,"forward-seek-offset",10):-E(b,"backward-seek-offset",10);ue(b,_)},E=function(c,b,_){return c.hasAttribute(b)&&parseInt(c.getAttribute(b))||_},B=function(c,b){let I=c==="ArrowUp"?Math.min(1,b.video.volume+.1):Math.max(0,b.video.volume-.1);O(b,I)},O=function(c,b){c.video.volume=b,c.video.muted&&b>0&&(c.video.muted=!1),c.hasAttribute("no-volume-pref")||localStorage.setItem("savedVolume",b.toString()),c.volumeControl.value=b,G(c),Q(c)},N=function(c,b,_){let I=S();c.progressBar.style.background=`linear-gradient(to right, ${c.accentColor} 0%, ${c.accentColor} ${b}%, ${c.primaryColor} ${b}%, ${c.primaryColor} ${_}%, ${I} ${_}%, ${I} 100%)`,c.progressBar.style.setProperty("--progressBar-thumb-position",`${b}%`)},q=function(c){V()&&Xe(c)},A=function(c,b,_){isFinite(b)&&(b>_&&R(c),b<_&&c.hls.trigger(ae().Events.BUFFER_FLUSHING,{startOffset:b,endOffset:Number.POSITIVE_INFINITY}),c.video.currentTime=b)},z=function(c){c.video.paused&&!c.userPaused?c.video.pause():c.video.paused||c.video.play().catch(b=>{})},W=function(c,b,_){c<=b&&P(_)};var t=w,i=k,r=y,a=v,s=T,o=E,l=B,u=O,n=N,d=q,f=A,p=z,C=W;e.isWaitingForKey=!1,e.video.addEventListener("loadedmetadata",()=>{ri()||e.castButton&&e.bottomRightDiv&&e.castButton.parentElement===e.bottomRightDiv&&e.bottomRightDiv.removeChild(e.castButton),li(e);let c=Array.from(e.video.textTracks);if(e.hasAttribute("disable-hidden-captions"))ve(e,{emitEvent:!1});else if(e.isOnline){let b=e.getAttribute?.("default-subtitle-track");if(typeof b=="string"&&b.trim()){let _=b.trim().toLowerCase(),I=c.findIndex(F=>!F||F.kind!=="subtitles"&&F.kind!=="captions"?!1:(F.label||"").toString().trim().toLowerCase()===_);if(I!==-1){ze(e,I,{emitEvent:!1});try{let F=c[I]?.language;F&&localStorage.setItem("subtitleLang",F)}catch{}}else St(e,c)}else St(e,c)}try{setTimeout(()=>{requestAnimationFrame(()=>{try{let b=e;if(typeof b.getSubtitleTracks!="function")return;let _=b.getSubtitleTracks(),I=Array.isArray(_)?_.length:0,F=typeof b._lastTracksReadySubtitleCount=="number"?b._lastTracksReadySubtitleCount:0;if(b._lastTracksReadySubtitleCount=I,I>0&&F===0&&typeof b.dispatchEvent=="function"){let he=typeof b.getAudioTracks=="function"?b.getAudioTracks():[];b.dispatchEvent(new CustomEvent("fastpixtracksready",{detail:{audioTracks:he,subtitleTracks:_,currentAudioId:typeof b.currentAudioTrackId=="number"?b.currentAudioTrackId:null,currentSubtitleId:typeof b.currentSubtitleTrackId=="number"?b.currentSubtitleTrackId:null,currentAudioTrackLoaded:Array.isArray(he)?he.find(be=>be?.isCurrent)??null:null,currentSubtitleLoaded:Array.isArray(_)?_.find(be=>be?.isCurrent)??null:null}}))}}catch{}})},0)}catch{}}),e.video.addEventListener("loadedmetadata",()=>{e.dispatchEvent(new Event("loadedmetadata"))}),e.video.addEventListener("volumechange",()=>{e.volumeControl.value=e.video.volume;let c=e.video.volume,b=e.video.muted;b&&(e.video.volume=0,e.volumeControl.value="0"),Q(e),G(e),ot(e),localStorage.setItem("media-volume",String(e.video.volume)),Z(c,b),V()&&_i(c)}),e.addEventListener("playbackidchange",c=>{try{document.pictureInPictureElement&&(e._reenterPiPOnReady=!0,document.exitPictureInPicture?.())}catch{}e.controlsContainer.style.setProperty("--controls","none");let _=c.detail.playbackId;e.playlistPanel?.querySelectorAll(".playlist-item.selected").forEach(F=>F.classList.remove("selected"));let I=e.playlistPanel?.querySelector(`[data-playback-id="${_}"]`);I&&(I.classList.add("selected"),I.scrollIntoView({behavior:"smooth",block:"nearest"}))}),e.video.addEventListener("pause",()=>{V()?Me("pause",e):e.__fpAudioSwitchHoldActive||(e.playPauseButton.innerHTML=te,e.wasManuallyPaused=!0),e.playPauseButton.disabled=!1}),e.video.addEventListener("play",()=>{V()?Me("play",e):(e.playPauseButton.innerHTML=le,e.wasManuallyPaused=!1);let c=e.wrapper?.querySelectorAll(".hotspot");c&&c.length>0&&(c.forEach(b=>b.remove()),e.isHotspotVisible=!1),e.hotspotPauseTimeout&&(clearTimeout(e.hotspotPauseTimeout),e.hotspotPauseTimeout=null)}),e.video.addEventListener("waiting",()=>{e.isLoading=!0,e.isBuffering=!0,R(e),e.playPauseButton.disabled=!1}),e.video.addEventListener("loadstart",()=>{(e.hasAttribute("autoplay-shorts")||e.hasAttribute("auto-play")||e.hasAttribute("loop-next"))&&(e.video.muted=!1,e.video.volume=1,e.controlsContainer.style.setProperty("--initial-play-button","none"))}),e.video.addEventListener("emptied",()=>{(e.hasAttribute("autoplay-shorts")||e.hasAttribute("auto-play")||e.hasAttribute("loop-next"))&&R(e)}),e.video.addEventListener("playing",()=>{e.isError&&Ie(e),Y(e)&&P(e),!e.video.paused&&e.video.readyState>=2&&Y(e)&&P(e),!e.isBuffering&&Y(e)&&P(e),Ft(e),e.playPauseButton.disabled=!1,e.pauseAfterLoading&&!e.resolutionSwitching&&(e.video.pause(),e.pauseAfterLoading=!1)}),e.video.addEventListener("canplay",()=>{e.isBuffering=!1,e.isLoading=!1,!(e.hasAttribute("auto-play")||e.hasAttribute("autoplay-shorts")||e.hasAttribute("loop-next"))&&Y(e)&&setTimeout(()=>P(e),10)}),e.video.addEventListener("canplaythrough",()=>{e.isBuffering=!1,e.isLoading=!1,!(e.hasAttribute("auto-play")||e.hasAttribute("autoplay-shorts")||e.hasAttribute("loop-next"))&&Y(e)&&setTimeout(()=>P(e),10)}),e.video.addEventListener("durationchange",()=>{!(e.hasAttribute("auto-play")||e.hasAttribute("autoplay-shorts")||e.hasAttribute("loop-next"))&&Y(e)&&P(e)}),e.video.addEventListener("loadedmetadata",()=>{if(ge(e),e.hasAutoPlayAttribute===!0){let c=e.hasAttribute("auto-play"),b=e.hasAttribute("loop-next");e.video.setAttribute("playsinline",""),e.video.setAttribute("webkit-playsinline",""),e.video.playsInline=!0,e.video.autoplay=!0,!c&&!b?(e.video.muted=!0,e.video.volume=0):(e.video.muted=!1,e.video.volume=1),x(e,e.playbackId,e.thumbnailUrlFinal,e.streamType),e.volumeControl.value=e.video.volume,G(e),Q(e),localStorage.setItem("savedVolume",e.volumeControl.value),localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML)}if(e.mutedAttribute===!0)e.video.muted=!0,e.video.volume=0,e.volumeControl.value=e.video.volume,G(e),Q(e),localStorage.setItem("savedVolume",e.volumeControl.value),localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML);else{localStorage.removeItem("muted");let c=localStorage.getItem("savedVolume");c!==null&&(e.video.volume=parseFloat(c),e.volumeControl.value=e.video.volume,G(e),Q(e));let b=localStorage.getItem("savedVolumeIcon");b!==null&&(e.volumeButton.innerHTML=b)}});let S=()=>getComputedStyle(e).getPropertyValue("--progress-bar-track-unfilled").trim()||"rgba(255,255,255,0.14)",g=null;e.video.addEventListener("play",k),e.video.addEventListener("playing",k),e.video.addEventListener("pause",y),e.video.addEventListener("ended",y),e.video.addEventListener("seeking",w),e.video.addEventListener("seeked",w);let h=null;e.video.addEventListener("timeupdate",()=>{h===null&&(h=requestAnimationFrame(()=>{h=null,!e.video.paused&&e.video.readyState>=2&&Y(e)&&P(e);let c=_t(e);e.skipIntroButton&&e.skipIntroStart!=null&&e.skipIntroEnd!=null?Number.isFinite(e.skipIntroStart)&&Number.isFinite(e.skipIntroEnd)&&c>=e.skipIntroStart&&c<=e.skipIntroEnd?e.skipIntroButton.style.display="block":e.skipIntroButton.style.display="none":e.skipIntroButton&&(e.skipIntroButton.style.display="none"),e.nextEpisodeButton&&e.nextEpisodeOverlayStart!=null&&Number.isFinite(e.nextEpisodeOverlayStart)?Array.isArray(e.playlist)&&e.currentIndex<(e.playlist?.length??0)-1&&c>=e.nextEpisodeOverlayStart?e.nextEpisodeButton.style.display="block":e.nextEpisodeButton.style.display="none":e.nextEpisodeButton&&(e.nextEpisodeButton.style.display="none"),ge(e),yt(e)}))}),e.video.addEventListener("progress",w),e.video.addEventListener("ended",()=>{if(e.hasAttribute("loop")){e.videoEnded=!1;return}e.videoEnded=!0,e.liveStreamDisplay.addEventListener("click",()=>{e.video.play(),e.videoEnded=!1}),e.loopPlaylistTillEnd&&(e.next(),e.videoEnded=!1)}),e.progressBar.addEventListener("keydown",c=>{if(!v(e))switch(c.code){case"ArrowLeft":case"ArrowRight":T(c.code,e);break;case"ArrowUp":case"ArrowDown":B(c.code,e);break}}),e.progressBar.addEventListener("input",()=>{let c=e.video.duration;if(!isFinite(c))return;let b=e.progressBar.value,_=b/100*c,I=e.video.buffered.length>0?e.video.buffered.end(e.video.buffered.length-1):0,F=I/c*100;N(e,b,F),q(_),V()||(A(e,_,I),z(e),W(_,I,e)),M(e),e.chapters>0&&(j(e),yt(e))}),e.skipIntroButton&&e.skipIntroButton.addEventListener("click",()=>{if(e.skipIntroEnd==null)return;let c=e.video.duration,b=Math.min((Number(e.skipIntroEnd)||0)+1,Number.isFinite(c)?Math.max(0,c-.1):(Number(e.skipIntroEnd)||0)+1),_=e.video.buffered.length>0?e.video.buffered.end(e.video.buffered.length-1):0;q(b),V()||(A(e,b,_),z(e),W(b,_,e))}),e.volumeControl.addEventListener("input",()=>{let c=e.getAttribute("primary-color")??"#F5F5F5",b=e.volumeControl.value,_=`linear-gradient(to right, ${c} 0%, ${c} ${(b*100).toFixed(2)}%, rgba(255, 255, 255, 0.1) ${(b*100).toFixed(2)}%, rgba(255, 255, 255, 0.1) 100%)`;e.volumeControl.style.background=_;let I=e.hasAttribute("no-volume-pref");I?localStorage.removeItem("savedVolumeIcon"):localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML),e.video.volume=b,b==="0"?e.video.muted=!0:e.video.muted=!1,e.volumeControl.value=b,G(e),Q(e),Z(b,e.video.muted),I?(localStorage.removeItem("savedVolumeIcon"),localStorage.removeItem("savedVolume")):(localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML),localStorage.setItem("savedVolume",e.video.volume.toString())),M(e)}),e.video.addEventListener("loadedmetadata",async()=>{Gt(e);let c=localStorage.getItem("savedVolume");if(c!==null&&!e.hasAttribute("no-volume-pref")){let b=parseFloat(c);e.video.volume=b,e.volumeControl.value=b.toString(),G(e),Q(e)}else e.video.volume=1,e.volumeControl.value="1",G(e),Q(e);e.video.playbackRate=e.defaultPlaybackRate,e.preloadAttribute==="auto"||e.preloadAttribute==="none"||e.preloadAttribute!==null?e.video.preload=e.preloadAttribute:e.video.preload="metadata",e.crossoriginAttribute!==null?e.video.crossOrigin=e.crossoriginAttribute:e.video.crossOrigin="",K(e)})}};function dr(e){localStorage.getItem("chromecastActive")==="true"&&(e.initialPlayClick=!0,R(e),e.controlsContainer.style.setProperty("--initial-play-button","none"),setTimeout(()=>{ie(()=>{Tt(e);let t=localStorage.getItem("pausedOnCasting")==="true";e.pausedOnCasting=t,e.pausedOnCasting?Me("pause",e):Me("play",e),P(e)})},1200))}function pr(e){e.ccButton.addEventListener("click",()=>{if(e.subtitleMenu&&e.subtitleMenu.style.display!=="none"){e.subtitleMenu.style.display="none";return}M(e),vi(e)})}function cr(e){e.audioMenuButton.addEventListener("click",()=>{if(e.audioMenu&&e.audioMenu.style.display!=="none"){e.audioMenu.style.display="none";return}M(e),Ze(e)})}function mr(e){e.fullScreenButton.addEventListener("click",()=>{M(e),$e(e)})}function Nt(e){e.forwardSeekOffset=e.forwardSeekAttribute?parseInt(e.forwardSeekAttribute):10,ue(e,e.forwardSeekOffset),M(e)}function qt(e){e.backwardSeekOffset=e.backwardSeekAttribute?parseInt(e.backwardSeekAttribute):10,ue(e,-e.backwardSeekOffset),M(e)}function hr(e){e.fastForwardButton.addEventListener("click",()=>{Nt(e)}),e.rewindBackButton.addEventListener("click",()=>{qt(e)})}function fr(e){e.volumeButton.addEventListener("click",()=>{let t=e.hasAttribute("no-volume-pref");t?localStorage.removeItem("savedVolumeIcon"):localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML);let i=parseFloat(localStorage.getItem("savedVolume")??"1");e.video.muted||i===0?(e.video.muted=!1,e.volumeButton.innerHTML=ee,e.volumeControl.value="1",e.video.volume=1):(e.video.muted=!0,e.volumeButton.innerHTML=se,e.volumeControl.value=0,e.video.volume=0),G(e),Q(e),t?(localStorage.removeItem("savedVolume"),localStorage.removeItem("savedVolumeIcon")):(localStorage.setItem("savedVolume",e.video.volume.toString()),localStorage.setItem("savedVolumeIcon",e.volumeButton.innerHTML)),Z(e.video.volume,e.video.muted),M(e)})}function yr(e){e.pipButton.addEventListener("click",()=>{document.pictureInPictureEnabled&&!e.video.disablePictureInPicture?document.pictureInPictureElement?document.exitPictureInPicture().then(()=>{}).catch(t=>{L(e,"Error exiting Picture-in-Picture")}):e.video.requestPictureInPicture().then(()=>{}).catch(t=>{L(e,"Error entering Picture-in-Picture")}):L(e,"Picture-in-Picture is not supported in this browser."),M(e)})}function br(e){e.playbackRateButton.addEventListener("click",()=>{if(e.playbackRateDiv&&e.playbackRateDiv.style.display!=="none"){e.playbackRateDiv.style.display="none";return}M(e),fi(e)})}var Us=e=>[e.progressBarContainer,e.volumeControl,e.pipButton,e.fullScreenButton,e.fastForwardButton,e.rewindBackButton,e.playPauseButton,e.timeDisplay,e.volumeButton,e.volumeiOSButton,e.skipIntroButton,e.nextEpisodeButton,e.resolutionMenu,e.resolutionMenuButton,e.playbackRateDiv,e.playbackRateButton,e.nextButton,e.prevButton,e.playlistButton,e.castButton].some(i=>i?.matches(":hover")),zt=e=>{e.progressBarContainer.style.opacity="1",e.volumeControl.style.opacity="1",e.pipButton.style.opacity="1",e.fullScreenButton.style.opacity="1",e.ccButton.style.opacity="1",e.fastForwardButton.style.opacity="1",e.rewindBackButton.style.opacity="1",e.playPauseButton.style.opacity="1",e.timeDisplay.style.opacity="1",e.parentVolumeDiv.style.opacity="1",e.volumeButton.style.opacity="1",e.playbackRateButton.style.opacity="1",e.volumeiOSButton.style.opacity="1",e.skipIntroButton.style.opacity="1",e.nextEpisodeButton.style.opacity="1",e.resolutionMenuButton.style.opacity="1",e.titleElement.style.opacity="1",e.controlsContainer.contains(e.mobileControls)&&(e.mobileControls.style.opacity="1"),e.controlsContainer.contains(e.castButton)&&(e.castButton.style.opacity="1"),e.controlsContainer.contains(e.playlistButton)&&(e.playlistButton.style.opacity="1"),e.controlsContainer.contains(e.playlistSlot)&&(e.playlistSlot.style.opacity="1"),e.leftControls.style.opacity="1",e.resolutionMenu.style.opacity="1",e.playbackRateDiv.style.opacity="1",e.liveStreamDisplay.style.opacity="1",e.titleElement.style.opacity="1",e.audioMenuButton.style.opacity="1",e.subtitleMenu.style.opacity="1",di(e),Vi(e),e.resetHideControlsTimer()},gr=e=>{e.lastInteractionTimestamp=Date.now(),e.lastKeyPressTimestamp=Date.now(),e.wrapper.addEventListener("keydown",()=>{zt(e),e.resetHideControlsTimer()}),e.resetHideControlsTimer=()=>{clearTimeout(e.hideControlsTimer),e.hideControlsTimer=setTimeout(t,3e3)};let t=()=>{if(e.initialPlayClick&&!e.video.paused&&!Us(e)){let r=Date.now()-e.lastInteractionTimestamp;r>=3e3||i()?(Ct(e,!0),Ct(e,!1),pi(e),Oi(e)):(clearTimeout(e.hideControlsTimer),e.hideControlsTimer=setTimeout(t,3e3-r))}},i=()=>Date.now()-e.lastKeyPressTimestamp<3e3;e.addEventListener("mousemove",()=>{zt(e)}),e.addEventListener("mouseout",r=>{setTimeout(()=>{e.contains(r?.relatedTarget)||t()},200)}),zt(e),e.video.addEventListener("click",()=>{x(e,e.playbackId,e.thumbnailUrlFinal,e.streamType),M(e)}),e.disableKeyboardControls||document.addEventListener("keydown",r=>{if(e.lastKeyPressTimestamp=Date.now(),e.hotKeys?.includes(r.code)){r.preventDefault();return}if(!e.initialPlayClick||e.retryButtonVisible)return;let a=e.hasAttribute("no-volume-pref");({KeyK:()=>{e.isLoading?e.pauseAfterLoading=e.video.paused:(x(e,e.playbackId,e.thumbnailUrlFinal,e.streamType),M(e))},ArrowUp:()=>e.video.volume<1&&Vt(e,.1,a),ArrowDown:()=>e.video.volume>0&&Vt(e,-.1,a),ArrowRight:()=>e.streamType!=="live-stream"&&(r.preventDefault(),Nt(e)),ArrowLeft:()=>e.streamType!=="live-stream"&&(r.preventDefault(),qt(e)),KeyM:()=>Ot(e,a),KeyF:()=>$e(e),KeyC:()=>hi(e)})[r.code]?.()})};function vr(e){let t,i=()=>{K(e),e.video.readyState>=1?j(e):e.video.addEventListener("loadedmetadata",()=>j(e),{once:!0}),e.video.offsetWidth>=471&&e.initialPlayClick&&(e.playPauseButton.style.position="absolute")};window.addEventListener("resize",()=>{clearTimeout(t),t=setTimeout(()=>{t=void 0,requestAnimationFrame(i)},120)}),window.addEventListener("load",()=>{requestAnimationFrame(()=>{K(e),j(e),dr(e)})}),window.addEventListener("DOMContentLoaded",()=>{let r=parseFloat(localStorage.getItem("savedVolume")??"0.6"),a=localStorage.getItem("savedVolumeIcon")??"";e.volumeControl.value=r.toString(),e.video.volume=r,a&&(e.volumeButton.innerHTML=a)})}var Cr=`<svg width="100%" height="100%" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g clip-path="url(#clip0_822_31051)">
    <path d="M7.44444 6.66675C6.1 6.66675 5 7.86675 5 9.33341V22.6667C5 24.1334 6.1 25.3334 7.44444 25.3334H24.5556C25.9 25.3334 27 24.1334 27 22.6667V9.33341C27 7.86675 25.9 6.66675 24.5556 6.66675H7.44444ZM7.44444 14.6667H11.1111V17.3334H7.44444V14.6667ZM18.4444 22.6667H7.44444V20.0001H18.4444V22.6667ZM24.5556 22.6667H20.8889V20.0001H24.5556V22.6667ZM24.5556 17.3334H13.5556V14.6667H24.5556V17.3334Z" fill="currentColor"/>
    <path d="M7.44444 5.66675C5.46759 5.66675 4 7.39815 4 9.33341V22.6667C4 24.602 5.46759 26.3334 7.44444 26.3334H24.5556C26.5324 26.3334 28 24.602 28 22.6667V9.33341C28 7.39815 26.5324 5.66675 24.5556 5.66675H7.44444ZM8.44444 15.6667H10.1111V16.3334H8.44444V15.6667ZM17.4444 21.6667H8.44444V21.0001H17.4444V21.6667ZM23.5556 21.6667H21.8889V21.0001H23.5556V21.6667ZM23.5556 16.3334H14.5556V15.6667H23.5556V16.3334Z" stroke="black" stroke-opacity="0.15" stroke-width="0"/>
    </g>
    <defs>
    <clipPath id="clip0_822_31051">
    <rect width="32" height="32" fill="currentColor"/>
    </clipPath>
    </defs>
</svg>`;var wr=`<svg width="100%" height = "100%" viewBox = "0 0 24 24" fill = "none" xmlns = "https://www.w3.org/2000/svg">
    <path d="M13.8 4.1421C13.8 3.5112 13.2888 3 12.6579 3H11.343C10.7112 3 10.2 3.5112 10.2 4.1421C10.2 4.6623 9.8436 5.1087 9.3585 5.2995C9.282 5.3301 9.2055 5.3625 9.1308 5.3949C8.6529 5.6019 8.085 5.5389 7.716 5.1708C7.50184 4.95679 7.21146 4.83657 6.9087 4.83657C6.60594 4.83657 6.31556 4.95679 6.1014 5.1708L5.1708 6.1014C4.95679 6.31556 4.83657 6.60594 4.83657 6.9087C4.83657 7.21146 4.95679 7.50184 5.1708 7.716C5.5398 8.085 5.6028 8.652 5.394 9.1308C5.36119 9.20615 5.32969 9.28206 5.2995 9.3585C5.1087 9.8436 4.6623 10.2 4.1421 10.2C3.5112 10.2 3 10.7112 3 11.3421V12.6579C3 13.2888 3.5112 13.8 4.1421 13.8C4.6623 13.8 5.1087 14.1564 5.2995 14.6415C5.3301 14.718 5.3625 14.7945 5.394 14.8692C5.6019 15.3471 5.5389 15.915 5.1708 16.284C4.95679 16.4982 4.83657 16.7885 4.83657 17.0913C4.83657 17.3941 4.95679 17.6844 5.1708 17.8986L6.1014 18.8292C6.31556 19.0432 6.60594 19.1634 6.9087 19.1634C7.21146 19.1634 7.50184 19.0432 7.716 18.8292C8.085 18.4602 8.652 18.3972 9.1308 18.6051C9.2055 18.6384 9.282 18.6699 9.3585 18.7005C9.8436 18.8913 10.2 19.3377 10.2 19.8579C10.2 20.4888 10.7112 21 11.3421 21H12.6579C13.2888 21 13.8 20.4888 13.8 19.8579C13.8 19.3377 14.1564 18.8913 14.6415 18.6996C14.718 18.6699 14.7945 18.6384 14.8692 18.606C15.3471 18.3972 15.915 18.4611 16.2831 18.8292C16.3892 18.9353 16.5151 19.0195 16.6537 19.0769C16.7923 19.1343 16.9408 19.1639 17.0908 19.1639C17.2409 19.1639 17.3894 19.1343 17.528 19.0769C17.6666 19.0195 17.7925 18.9353 17.8986 18.8292L18.8292 17.8986C19.0432 17.6844 19.1634 17.3941 19.1634 17.0913C19.1634 16.7885 19.0432 16.4982 18.8292 16.284C18.4602 15.915 18.3972 15.348 18.6051 14.8692C18.6384 14.7945 18.6699 14.718 18.7005 14.6415C18.8913 14.1564 19.3377 13.8 19.8579 13.8C20.4888 13.8 21 13.2888 21 12.6579V11.343C21 10.7121 20.4888 10.2009 19.8579 10.2009C19.3377 10.2009 18.8913 9.8445 18.6996 9.3594C18.6694 9.28295 18.6379 9.20704 18.6051 9.1317C18.3981 8.6538 18.4611 8.0859 18.8292 7.7169C19.0432 7.50274 19.1634 7.21236 19.1634 6.9096C19.1634 6.60684 19.0432 6.31646 18.8292 6.1023L17.8986 5.1717C17.6844 4.95769 17.3941 4.83747 17.0913 4.83747C16.7885 4.83747 16.4982 4.95769 16.284 5.1717C15.915 5.5407 15.348 5.6037 14.8692 5.3958C14.7939 5.36269 14.7179 5.33088 14.6415 5.3004C14.1564 5.1087 13.8 4.6614 13.8 4.1421Z" stroke = "currentColor" stroke - width="1.5" stroke - line - cap="round" stroke - line - join="round" />
    <path d="M15.6 12C15.6 12.9548 15.2207 13.8705 14.5456 14.5456C13.8705 15.2207 12.9548 15.6 12 15.6C11.0452 15.6 10.1295 15.2207 9.45442 14.5456C8.77928 13.8705 8.4 12.9548 8.4 12C8.4 11.0452 8.77928 10.1295 9.45442 9.45442C10.1295 8.77928 11.0452 8.4 12 8.4C12.9548 8.4 13.8705 8.77928 14.5456 9.45442C15.2207 10.1295 15.6 11.0452 15.6 12Z" stroke = "currentColor" stroke - width="1.5" stroke - line - cap="round" stroke - line - join="round" />
</svg>`;var kr=`<svg width="100%" height = "100%" viewBox = "0 0 24 24" fill = "none" xmlns = "http://www.w3.org/2000/svg" >
    <path d="M9.5 14C11.71 14 13.5 12.21 13.5 10C13.5 7.79 11.71 6 9.5 6C7.29 6 5.5 7.79 5.5 10C5.5 12.21 7.29 14 9.5 14ZM9.5 8C10.6 8 11.5 8.9 11.5 10C11.5 11.1 10.6 12 9.5 12C8.4 12 7.5 11.1 7.5 10C7.5 8.9 8.4 8 9.5 8Z" fill = "currentColor" stroke = "currentColor" stroke-width="0" />
    <path d="M15.89 16.56C14.21 15.7 12.03 15 9.5 15C6.97 15 4.79 15.7 3.11 16.56C2.11 17.07 1.5 18.1 1.5 19.22V22H17.5V19.22C17.5 18.1 16.89 17.07 15.89 16.56ZM15.5 20H3.5V19.22C3.5 18.84 3.7 18.5 4.02 18.34C5.21 17.73 7.13 17 9.5 17C11.87 17 13.79 17.73 14.98 18.34C15.3 18.5 15.5 18.84 15.5 19.22V20Z" fill="currentColor" stroke="currentColor" stroke-width="0" />
    <path d="M15.5 2H13.5C13.5 6.97 17.53 11 22.5 11V9C18.64 9 15.5 5.86 15.5 2Z" fill = "currentColor" stroke = "currentColor" stroke-width="0" />
    <path d="M19.5 2H17.5C17.5 4.76 19.74 7 22.5 7V5C20.85 5 19.5 3.65 19.5 2Z" fill = "currentColor" stroke = "currentColor" stroke-width="0" />
</svg>`;var Sr=`<svg width="100%" height="100%" id="pipButtonSvg" viewBox="0 0 41 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M28.2778 22.8889H19.3889V29.5556H28.2778V22.8889ZM32.7223 31.7778V16.2C32.7223 14.9778 31.7223 14 30.5001 14H10.5001C9.27783 14 8.27783 14.9778 8.27783 16.2V31.7778C8.27783 33 9.27783 34 10.5001 34H30.5001C31.7223 34 32.7223 33 32.7223 31.7778ZM30.5001 31.8H10.5001V16.1889H30.5001V31.8Z" fill="currentColor"/>
  <path d="M28.2778 22.8889H19.3889V29.5556H28.2778V22.8889ZM32.7223 31.7778V16.2C32.7223 14.9778 31.7223 14 30.5001 14H10.5001C9.27783 14 8.27783 14.9778 8.27783 16.2V31.7778C8.27783 33 9.27783 34 10.5001 34H30.5001C31.7223 34 32.7223 33 32.7223 31.7778ZM30.5001 31.8H10.5001V16.1889H30.5001V31.8Z" fill="currentColor"/>
</svg>`;var Er=`<svg id="forwardSeekBtnSvg" width="24" height="24" viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24.5 18.6667C24.5 23.08 20.9133 26.6667 16.5 26.6667C12.0867 26.6667 8.50001 23.08 8.50001 18.6667C8.50001 14.2533 12.0867 10.6667 16.5 10.6667V16L23.1667 9.33332L16.5 2.66666V7.99999C10.6067 7.99999 5.83334 12.7733 5.83334 18.6667C5.83334 24.56 10.6067 29.3333 16.5 29.3333C22.3933 29.3333 27.1667 24.56 27.1667 18.6667H24.5Z" fill="currentColor"/>
    <path d="M15.0333 22.6667V16.9733H14.9133L12.5533 17.8133V18.7333L13.9 18.32V22.6667H15.0333Z" fill="currentColor"/>
    <path d="M19.5933 17.04C19.3533 16.9467 19.1 16.9067 18.8067 16.9067C18.5133 16.9067 18.26 16.9467 18.02 17.04C17.78 17.1333 17.58 17.28 17.42 17.48C17.26 17.68 17.1133 17.9333 17.0333 18.24C16.9533 18.5467 16.9 18.9067 16.9 19.3333V20.32C16.9 20.7467 16.9533 21.12 17.0467 21.4133C17.14 21.7067 17.2733 21.9733 17.4467 22.1733C17.62 22.3733 17.82 22.52 18.06 22.6133C18.3 22.7067 18.5533 22.7467 18.8467 22.7467C19.14 22.7467 19.3933 22.7067 19.6333 22.6133C19.8733 22.52 20.0733 22.3733 20.2333 22.1733C20.3933 21.9733 20.5267 21.72 20.62 21.4133C20.7133 21.1067 20.7533 20.7467 20.7533 20.32V19.3333C20.7533 18.9067 20.7 18.5333 20.6067 18.24C20.5133 17.9467 20.38 17.68 20.2067 17.48C20.0333 17.28 19.82 17.1333 19.5933 17.04ZM19.6067 20.4667C19.6067 20.72 19.5933 20.9333 19.5533 21.1067C19.5133 21.28 19.4733 21.4267 19.4067 21.5333C19.34 21.64 19.26 21.72 19.1533 21.76C19.0467 21.8 18.94 21.8267 18.82 21.8267C18.7 21.8267 18.58 21.8 18.4867 21.76C18.3933 21.72 18.3 21.64 18.2333 21.5333C18.1667 21.4267 18.1133 21.28 18.0733 21.1067C18.0333 20.9333 18.02 20.72 18.02 20.4667V19.1733C18.02 18.92 18.0333 18.7067 18.0733 18.5333C18.1133 18.36 18.1533 18.2267 18.2333 18.12C18.3133 18.0133 18.38 17.9333 18.4867 17.8933C18.5933 17.8533 18.7 17.8267 18.82 17.8267C18.94 17.8267 19.06 17.8533 19.1533 17.8933C19.2467 17.9333 19.34 18.0133 19.4067 18.12C19.4733 18.2267 19.5267 18.36 19.5667 18.5333C19.6067 18.7067 19.62 18.92 19.62 19.1733V20.4667H19.6067Z" fill="currentColor"/>
</svg>`;var Tr=`<svg width="24" height="24" viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M16.5 7.99999V2.66666L9.83334 9.33332L16.5 16V10.6667C20.9133 10.6667 24.5 14.2533 24.5 18.6667C24.5 23.08 20.9133 26.6667 16.5 26.6667C12.0867 26.6667 8.50001 23.08 8.50001 18.6667H5.83334C5.83334 24.56 10.6067 29.3333 16.5 29.3333C22.3933 29.3333 27.1667 24.56 27.1667 18.6667C27.1667 12.7733 22.3933 7.99999 16.5 7.99999ZM15.0333 22.6667H13.9V18.32L12.5533 18.7333V17.8133L14.9133 16.9733H15.0333V22.6667ZM20.74 20.32C20.74 20.7467 20.7 21.12 20.6067 21.4133C20.5133 21.7067 20.38 21.9733 20.22 22.1733C20.06 22.3733 19.8467 22.52 19.62 22.6133C19.3933 22.7067 19.1267 22.7467 18.8333 22.7467C18.54 22.7467 18.2867 22.7067 18.0467 22.6133C17.8067 22.52 17.6067 22.3733 17.4333 22.1733C17.26 21.9733 17.1267 21.72 17.0333 21.4133C16.94 21.1067 16.8867 20.7467 16.8867 20.32V19.3333C16.8867 18.9067 16.9267 18.5333 17.02 18.24C17.1133 17.9467 17.2467 17.68 17.4067 17.48C17.5667 17.28 17.78 17.1333 18.0067 17.04C18.2333 16.9467 18.5 16.9067 18.7933 16.9067C19.0867 16.9067 19.34 16.9467 19.58 17.04C19.82 17.1333 20.02 17.28 20.1933 17.48C20.3667 17.68 20.5 17.9333 20.5933 18.24C20.6867 18.5467 20.74 18.9067 20.74 19.3333V20.32ZM19.6067 19.1733C19.6067 18.92 19.5933 18.7067 19.5533 18.5333C19.5133 18.36 19.46 18.2267 19.3933 18.12C19.3267 18.0133 19.2467 17.9333 19.14 17.8933C19.0333 17.8533 18.9267 17.8267 18.8067 17.8267C18.6867 17.8267 18.5667 17.8533 18.4733 17.8933C18.38 17.9333 18.2867 18.0133 18.22 18.12C18.1533 18.2267 18.1 18.36 18.06 18.5333C18.02 18.7067 18.0067 18.92 18.0067 19.1733V20.4667C18.0067 20.72 18.02 20.9333 18.06 21.1067C18.1 21.28 18.1533 21.4267 18.22 21.5333C18.2867 21.64 18.3667 21.72 18.4733 21.76C18.58 21.8 18.6867 21.8267 18.8067 21.8267C18.9267 21.8267 19.0467 21.8 19.14 21.76C19.2333 21.72 19.3267 21.64 19.3933 21.5333C19.46 21.4267 19.5133 21.28 19.54 21.1067C19.5667 20.9333 19.5933 20.72 19.5933 20.4667V19.1733H19.6067Z" fill="currentColor"/>
</svg>`;var Br=`

/* Register --progressBar-thumb-position as a typed property so CSS can
   interpolate it smoothly when the value changes (e.g. during seeks). */
@property --progressBar-thumb-position {
  syntax: '<percentage>';
  inherits: false;
  initial-value: 0%;
}

/* CSS Variables */
:host {
    --icon-width: 24px;
    --icon-height: 30px;
    --icon-big-width: 30px;
    --icon-big-height: 30px;
    --button-width: 32px;
    --button-height: 32px;
    --button-big-width: 64px;
    --button-big-height: 64px;
    --font-size: 16px;
    --border-radius: 3px;
    --media-object-fit: cover;
    --media-object-position: center;
    --accent-color: #5D09C7;
    --primary-color: #F5F5F5;
    --secondary-color: #000;
    --thumbnail-max-width: 150px;
    --cast-button-display: flex;
    --previous-episode-button: flex;
    --shoppable-sidebar-width: 30%;
    --shoppable-sidebar-background-color: rgba(255, 255, 255, 0.75);
    /* User slot overlay (see SLOTS_DEVELOPER_GUIDE.md) */
    --user-slot-z: 6;
    /* Avoid vh in default \u2014 mobile URL bar and early layout make vh-based padding jump. Override per app. */
    --user-slot-bottom-clearance: 64px;
    --play-button-initialized: flex;
    --mobile-play-button: flex;
    --mobile-play-button-initialized: flex;
    --player-border-radius: 0px;
    --progress-bar-track-unfilled: rgba(255, 255, 255, 0.14);
    aspect-ratio: 16 / 9;
    display: block; /* Ensure the custom element is a block-level element */
    font-family: Arial, sans-serif;
    aspect-ratio: var(--aspect-ratio); /* Use the aspect ratio variable */
    /* Lets page CSS and integrators use @container queries against player width (not just the viewport). */
    container-type: inline-size;
    container-name: fp-player;
}

:host(:focus) {
    outline: none;
}
        
video {
    width: 100%;
    height: 100%;
    display: block;
    // max-width: 100% !important; /* Ensure the video does not exceed its container */
    // max-height: 100% !important; /* Ensure the video does not exceed its container */
    object-fit: contain; /* Adjust this based on your requirement */
    overflow: hidden;
    background-color: #000; /* Fallback color */
    border-radius: var(--player-border-radius);
    /* WebKit: video composits in its own layer and can paint above later siblings unless z-order is explicit */
    position: relative;
    z-index: 0;
}

google-cast-launcher {
  width: 40px;
  height: 40px;
  cursor: pointer;
  color: #fff;
}

  
.video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none; /* Allow clicks to pass through the overlay to the video */
}

/* Declarative slots: light-DOM children with slot="top-right" (etc.) compose here. */
.fastpix-user-slots {
    position: absolute;
    inset: 0;
    z-index: var(--user-slot-z, 6);
    pointer-events: none;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    grid-template-rows: auto minmax(0, 1fr) auto;
    box-sizing: border-box;
    padding: 8px 8px calc(8px + var(--user-slot-bottom-clearance, 64px)) 8px;
    transition: none;
}

.fastpix-slot-region {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 6px;
    max-width: 100%;
    min-width: 0; /* allow shrinking inside grid / flex so slotted UI can respond to narrow hosts */
    pointer-events: none;
}

.fastpix-slot-region ::slotted(*) {
    pointer-events: auto;
}

.fastpix-slot-top-left {
    grid-column: 1;
    grid-row: 1;
    align-self: start;
    justify-self: start;
}

.fastpix-slot-top-center {
    grid-column: 2;
    grid-row: 1;
    align-self: start;
    justify-self: center;
    justify-content: center;
}

.fastpix-slot-top-right {
    grid-column: 3;
    grid-row: 1;
    align-self: start;
    justify-self: end;
    justify-content: flex-end;
}

.fastpix-slot-center-left {
    grid-column: 1;
    grid-row: 2;
    align-self: center;
    justify-self: start;
    align-content: center;
}

.fastpix-slot-center-right {
    grid-column: 3;
    grid-row: 2;
    align-self: center;
    justify-self: end;
    justify-content: flex-end;
    align-content: center;
}

.fastpix-slot-bottom-left {
    grid-column: 1;
    grid-row: 3;
    align-self: end;
    justify-self: start;
    align-items: flex-end;
}

.fastpix-slot-bottom-center {
    grid-column: 2;
    grid-row: 3;
    align-self: end;
    justify-self: center;
    justify-content: center;
    align-items: flex-end;
}

.fastpix-slot-bottom-right {
    grid-column: 3;
    grid-row: 3;
    align-self: end;
    justify-self: end;
    justify-content: flex-end;
    align-items: flex-end;
}

/* Narrow player: tighter slot chrome (uses host container from :host). */
@container fp-player (max-width: 520px) {
    .fastpix-user-slots {
        padding: 5px 5px calc(5px + var(--user-slot-bottom-clearance, 64px)) 5px;
    }
    .fastpix-slot-region {
        gap: 4px;
    }
}

@container fp-player (max-width: 380px) {
    .fastpix-user-slots {
        padding: 3px 3px calc(3px + var(--user-slot-bottom-clearance, 64px)) 3px;
    }
    .fastpix-slot-region {
        gap: 3px;
    }
}

.overlay-show {
    background-color: var(--backdrop-color, transparent);
}

.parent.subtitle-container {
    opacity: 0;
}

.parent.initialized .subtitle-container {
    opacity: 1;
}

.subtitle-container.contained {
    position: absolute;
    bottom: 10%; /* Adjust this value as needed to position the subtitles */
    left: 50%;
    transform: translateX(-50%);
    width: auto; /* Allows the width to adjust based on content */
    pointer-events: none; /* Allows interaction with the video element */
    transition: bottom 0.6s ease; /* Smooth transition */
    text-align: center;
    background: rgba(0, 0, 0, 0.4); /* Semi-transparent black background */
    color: white;
    padding: 0.25em 0.5em; /* Adds some padding for better readability */
    border-radius: 3px; /* Optional: adds a slight border-radius */
    overflow-y: hidden;
}

/* Default subtitle position */
.subtitle-container.large {
    bottom: 20px; /* Adjust this value to set the default position */
    font-size: 24px;
}

/* Class to move subtitles up */
.subtitles-up .subtitle-container.large {
    bottom: 98px; /* Adjust this value to move subtitles up */
    font-size: 24px;
}

.subtitle-container.medium {
    bottom: 20px; /* Adjust this value to set the default position */
    font-size: 14px;
}

/* Class to move subtitles up */
.subtitles-up .subtitle-container.medium {
    bottom: 70px; /* Adjust this value to move subtitles up */
    font-size: 14px;
    max-height: 150px;
}

.subtitle-container.mobile {
    bottom: 20px;
    font-size: 8px;
}

.subtitles-up .subtitle-container.mobile {
    bottom: 50px; /* Adjust this value to move subtitles up */
    font-size: 8px;
    max-height: 90px;
    position: absolute;
    width: calc(100% - 100px);
}
    
/* General ::cue styling */
::cue {
    display: none !important;
    background: none !important;
    color: transparent !important;
    text-shadow: none !important;
    box-shadow: none !important;
    border: none !important;
    outline: none !important;
}

/* Specific video::cue styling */
video::cue {
    display: none !important;
    background: none !important;
    color: transparent !important;
    text-shadow: none !important;
    box-shadow: none !important;
    border: none !important;
    outline: none !important;
}

/* Fallback for Webkit-based browsers */
video::-webkit-media-text-track-display {
    display: none !important;
    background: none !important;
    color: transparent !important;
    text-shadow: none !important;
    box-shadow: none !important;
    border: none !important;
    outline: none !important;
}

.leftControls.initialized {
    display: var(--left-controls-bottom, flex)
}

.leftControls.mobile.initialized {
    display: var(--left-controls-bottom-mobile, flex);
    bottom: 3px;
    position: absolute;
}

.bottomRightContainer.mobile.initialized {
    display: var(--bottom-right-controls-mobile, flex)
}

.controlsContainer {
    display: var(--controls, flex);
    /* Stack above WebKit video layer; fill parent so absolute children align to the player */
    position: absolute;
    inset: 0;
    z-index: 2;
    /* Full-cover layer would steal taps from <video>; pass through except on real controls */
    pointer-events: none;
}

.controlsContainer * {
    pointer-events: auto;
}

/* Inert flex row \u2014 must not block tap-to-toggle on the video */
.controlsContainer .bottomCenterDiv {
    pointer-events: none;
}

.volumeiOSButton {
    display: var(--volume-iOS-button, flex)
}

.castButton {
    display: var(--cast-button-display, flex);
}
    
.playlistButtonVisible {
    display: var(--playlist-button-visible, flex);
}

#decreaseTimeBtn,
#increaseTimeBtn,
.timeDisplay,
.parentVolumeDiv,
.initialplayPauseButtonStyle,
.castButton {
    border-radius: var(--border-radius);
}

#forwardSeekBtnSvg {
    height: 24px;
     width: 24px;
}

.roundedCorners {
     border-radius: 50%;
}

.bottomCenterDiv {
    display: flex
}

.initialplayPauseButtonStyle {
    display: flex;
    align-items: center;
    justify-content: center;
}

.playbackRateButtonInitial {
    height: var(--icon-height);
    width: var(--icon-width);
    color: var(--primary-color);
    font-size: 14px;
}

.playbackRateButtonInitial:hover,
.audioMenuButton:hover,
.castButton:hover,
.playlistButton:hover,
.playlistPrevButton:hover {
    background-color: var(--accent-color); /* Color on hover */
    border-radius: 2px;
}

.playbackRateButton {
    border: 1px solid transparent;
    margin-right: 3px;
    color: #100023;
}

.playbackRateButton.active {
    background-color: var(--accent-color); /* Color on hover */
    color: var(--primary-color);
    border-radius: 2px;
}

.volumeiOSButton {
    color: var(--primary-color);
}

.parent.mobile.resolution-menu {
    bottom: 36px;
    right: 0;
}

.resolution-menu {
    display: flex;
    flex-direction: column;
    position: absolute;
    background-color: var(--primary-color);
    padding: 5px 7px;
    border-radius: 2px;
    font-size: 14px;
    color: #100023;
    bottom: 46px;
    overflow-y: auto;
    left: 0;
    right: auto;
}

.title,
.title-on-demand {
    display: none;
    color: var(--primary-color);
}

.title-on-demand.initialized {
    display: var(--title, flex);
    align-items: center;
    justify-content: center;
    margin-left: 10px;
    font-weight: 600;
}

.title-on-demand.mobile.initialized {
     display: none;
 }

.title.initialized {
    display: var(--title, flex);
    align-items: center;
    justify-content: center;
    margin-left: 60px;
    font-weight: 600;
    font-size: 14px;
}

.liveTag {
    position: absolute;
    color: #F5F5F5;
    margin-right:90px;
    padding: 2px 12px;
    font-size: 14px;
    font-weight: 600;
}

.liveTag::before {
    display: block;
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    position: absolute;
    left: 0px;
    top: calc(50% - 3px);
    background-color: red;
}

.parentTextContainer {
    display: none;
    position: absolute;
    padding: 10px 20px;
    left: 0;
    top: 10px;}

.parent.initialized .parentTextContainer {
    display: flex;
    flex-direction: row;
    width: 100%;
    justify-content: space-between;
    align-items: center;
}

.title.initialized {
    display: var(--title, flex);
    align-items: center;
    justify-content: center;
}

.qualitySelectorButtons,
.audioSelectorButtons,
.subtitleSelectorButtons,
.offSubtitles {
    padding: 6px 10px 6px 20px;
    position: relative;
    white-space: nowrap;
    text-overflow: ellipsis;
    text-transform: capitalize; 
}

.parent.initialized.mobile .qualitySelectorButtons,
.parent.initialized.mobile .audioSelectorButtons,
.parent.initialized.mobile .subtitleSelectorButtons,
.parent.initialized.mobile .offSubtitles {
    padding: 6px 10px 6px 15px;
}

.qualitySelectorButtons:hover,
.audioSelectorButtons:hover,
.subtitleSelectorButtons:hover,
.offSubtitles:hover {
    border-radius: 2px;
    background: var(--accent-color);
    color: var(--primary-color);
}

.playbackRateButton {
    color: #10023;
}

.playbackRateButton:hover {
    border-color: var(--accent-color); /* Color on hover */
    border-radius: 2px;
}

#playPauseAferClickBreakPoint {
    align-items: center;
    justify-content: center;
}

#playPauseAferClickBreakPoint:hover {
    border-radius:2px;
    transition: background-color 0.2s ease-in;
}

.parent {
    position: relative;             /* Anchor bottom gradient to the player */
    display: flex;
    row-gap: 1.875rem;
    height: 100%;
    overflow: hidden;               /* Keep gradient inside rounded corners */
    /* Do not transition layout properties: animating width/height on load made absolute overlays (user slots) drift. */
    transition: none;
}

.parent::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 80px; /* Softer scrim, keeps controls colors closer to original */
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.45),
        rgba(0, 0, 0, 0.20),
        rgba(0, 0, 0, 0)
    ); /* Bottom gradient shadow similar to Mux */
    pointer-events: none; /* Allow clicks to pass through the gradient background */
    border-radius: 0 0 10px 10px; /* Apply border-radius to match the video's border-radius */
}

#playPauseButtonId:hover {
    background-color: blue;
    border-radius: 2px;
    transition: background-color 0.3s ease-in;
}

.playbackRatesButton:hover,
.playlistNextButton:hover {
    background-color: var(--accent-color);
    color: var(--primary-color);
}
    
.parentVolumeDiv {
    display: none;
    flex-direction: row;
    /* position: absolute; */
    width: auto;
    justify-content: space-between;
    align-items: center;
}

#parentVolumeDivResponse {
    display: flex;
    flex-direction: row;
    position: absolute;
    left: 5%;
    width: auto;
    justify-content: space-between;
     align-items: center;
    bottom: 0;
}

#forwardRewindControlsWrapperResponsive,
#forwardRewindControlsWrapperMini {
    z-index: 1;
    position: absolute;
    width: 120px;
    left: 50%;
    bottom: 50%;
    transform: translateX(-50%);
    display: flex;
    justify-content: space-between;
}

#forwardRewindControlsWrapperMd {
    display: flex;
    flex-direction: row;
}

.qualitySelectorButtons.active::before,
.audioSelectorButtons.active::before,
.subtitleSelectorButtons.active::before,
.offSubtitles.active::before {
    display: block;
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    position: absolute;
    left: 5px;
    top: calc(50% - 3px);
    background-color: var(--accent-color);
}

.qualitySelectorButtons.active:hover::before,
.audioSelectorButtons.active:hover::before,
.subtitleSelectorButtons:hover::before,
.offSubtitles.active:hover::before {
    background-color: var(--primary-color);
}

.qualitySelectorButtons.active,
.audioSelectorButtons.active,
.subtitleSelectorButtons.active,
.offSubtitles.active {
    font-weight: bold;
}

.forwardRewindControlsWrapper {
    display: flex;
}

.playPauseBeforeClick {
    display:flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    bottom: 50%;
    left: 45%;
    color: var(--primary-color);
    height: 40px;
    width: 40px;
    border-radius: 50%;
}

.playPauseBeforeClick:hover,
.resolutionMenuButton:hover {
    background-color: var(--accent-color);
}

.volumeButton {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius);
}

.volumeButton:hover {
    background-color: var(--accent-color);
}

.bottomRightContainer {
    display: none;
    flex-direction: row;
    position: absolute;
    right: 20px;
    width: auto;
    justify-content: space-between;
    align-items: center;
    bottom: 0;
    z-index: 8;
}

.bottomRightContainer.initialized {
    display: var(--bottom-right-controls, flex);
}

#bottomRightDivMd {
bottom: 10px;
    right: 18px;
}

#increaseTimeBtn,
#decreaseTimeBtn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
}

#increaseTimeBtn:hover,#decreaseTimeBtn:hover {
    background-color: var(--accent-color);
    border-radius: 2px;
}

.subtitle-menu,
.audio-menu {
    display: flex;
    flex-direction: column;
    position: absolute;
    background-color: var(--primary-color);
    padding: 5px 7px;
    border-radius: 2px;
    font-size: 14px;
    color: #100023;
    bottom: 46px;
    overflow-y: auto;
    left: 32px;
    right: auto;
    max-width: 116px;
    overflow-y: auto;
    white-space: nowrap;
    text-overflow: ellipsis;        
}

.timeDisplay {
    font-family: sans-serif;
    font-size: 0.875rem;
    color: var(--primary-color);
    padding: 0px 5px;
    white-space: nowrap;
    border-radius: var(--border-radius);
}

#playPauseButtonHeightWidth {
    position: absolute;
    bottom: 50%;
}

/* Additional styling for each button */
.fullScreenButton:hover,
.pipButton:hover,
.ccButton:hover {
    background-color: var(--accent-color);}

.spinner {
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-left-color: var(--accent-color);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 0.5s linear infinite; /* Changed duration to 0.5s */
    position: absolute; /* Position the spinner relative to the viewport */
    top: 50%; /* Align the spinner vertically at the center of the viewport */
    left: 50%; /* Align the spinner horizontally at the center of the viewport */
    transform: translate(-50%, -50%); /* Center the spinner precisely */
    z-index: 9999; /* Ensure the spinner is on top of other elements */
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.retryButton {
    color: var(--accent-color);
}


/* Default styles for volume controls */
.volumeControl {
    width: 3.5rem; /* Adjust width as needed */
    display: inline-block;
    -webkit-appearance: none;
    border-radius: 0.313rem;
    height: 3px;
    background: linear-gradient(to right, var(--primary-color) 0%, var(--primary-color) 100%, #ddd 50%, #ddd 100%);
}

/* iOS volume mode: when iOS-specific button is active, hide standard slider/button */
.parentVolumeDiv.volumeControliOS .volumeControl,
.parentVolumeDiv.volumeControliOS .volumeButton {
    display: none !important;
}
.parentVolumeDiv.volumeControliOS .volumeiOSButton {
    display: flex !important;
}

/* Styling the volume control thumb */
.volumeControl::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 10px; /* Adjust thumb width as needed */
    height: 10px; /* Adjust thumb height as needed */
    background-color: var(--primary-color); /* Thumb color */
    border-radius: 50%; /* Make thumb round */
    cursor: pointer; /* Show pointer cursor */
    position: relative; /* Required for positioning the dot */
}

/* Styling the volume control thumb on hover */
.volumeControl:hover::-webkit-slider-thumb {
    visibility: visible; /* Show the thumb on hover */
}

/* Additional styles for the thumb */
.volumeControl::-webkit-slider-thumb::before {
    content: ""; /* No content for the pseudo-element */
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 6px; /* Adjust the size of the dot as needed */
    height: 6px; /* Adjust the size of the dot as needed */
    background-color: white; /* Color of the dot */
    border-radius: 50%;
 }

/* Styling the volume control thumb for Firefox */
.volumeControl::-moz-range-thumb {
    width: 10px; /* Adjust thumb width as needed */
    height: 10px; /* Adjust thumb height as needed */
    background-color: var(--primary-color); /* Thumb color */
    border-radius: 50%; /* Make thumb round */
    cursor: pointer; /* Show pointer cursor */
    border: none; /* Remove default border */
    -moz-appearance: none; /* Remove default styling */}

/* Additional styles for the thumb */
.volumeControl::-moz-range-thumb::before {
    content: ""; /* No content for the pseudo-element */
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 6px; /* Adjust the size of the dot as needed */
    height: 6px; /* Adjust the size of the dot as needed */
    background-color: var(--accent-color); /* Color of the dot */
    border-radius: 50%;
}

.playPauseButton {
    background-color: rgba(255, 255, 255, 0.1);
    border: none;
    cursor: pointer;
    fill: white;
    outline: none;
    width: 3.75rem;
    height: 3.75rem;
    border-radius: 50%;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    bottom: 45%;
}

.playPauseButton:hover {
    background-color: var(--accent-color);
}

#playBackAfterClick {
    background-color: rgba(255, 255, 255, 0.1)
    right: 45%;
    width: 2.5rem;
    height: 2.5rem;
    bottom: 0%;
}

#playBackAfterClick:hover {
    background-color: var(--accent-color);
}

.timeDisplay:hover {
    background-color: var(--accent-color);
}

/* Skip Intro button */
.skipIntroButton,
.nextEpisodeButton {
    position: absolute;
    bottom: 60px; /* slightly above the progress bar */
    background-color: #f5f5f5;
    color: black;
    font-weight: 600;
    padding: 6px 12px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    display: none;
    z-index: 1500;
    font-size: 14px;
    transition: background-color 150ms ease, color 150ms ease;
}



.controlsContainer .skipIntroButton:hover,
.controlsContainer .nextEpisodeButton:hover {
  background-color: var(--accent-color);
  color: #f5f5f5;
}

.skipIntroButton {
    left: 20px;
}

.nextEpisodeButton {
    right: 20px;
}

.progressBar.initialized {
    display: var(--progress-bar, flex);
    /* When --progress-bar-invisible: 1, bar is hidden but still receives hover/click for timestamp preview */
    opacity: calc(1 - var(--progress-bar-invisible, 0));
    pointer-events: auto;
 }

.progressBar.initialized.mobile {
    display: var(--progress-bar, flex);
    opacity: calc(1 - var(--progress-bar-invisible, 0));
    pointer-events: auto;
}

/* Only Firefox */
@supports (-moz-appearance:none) {
    .pipButton {
        display: var(--pip-button, none) !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }
}

#progressBar {
    position: absolute;
    height: 4.5px;
    bottom: var(--seekbar-bottom, 46px);
    left: 20px;
    right: 20px;
    margin: 0;
    padding: 0;
    cursor: pointer;
    -moz-appearance: none;
}

#progressBarResponsiveMd {
    position: absolute;
    height: 3.5px;
    bottom: var(--seekbar-bottom, 46px);
    left: 20px;
    right: 20px;
    cursor: pointer;
    width: calc(100% - 40px);
}

.chapter-marker-mini {
    height: 4.5px;
    bottom: 35px;
    position: absolute;
    left: 0;
    width: 1px;
    background-color: rgba(0, 0, 0, 0.4);
}

.chapter-marker-md {
    bottom: 47px;
    height: 4.5px;
    position: absolute;
    left: 0;
    width: 2.5px;
    background-color: rgba(0, 0, 0, 0.4); 
}

.chapter-marker-lg {
    bottom: 46px;
    height: 4.5px;
    position: absolute;
    left: 0;
    width: 2.5px;
    background-color: rgba(0, 0, 0, 0.4);
} 

.progressBar {
    display: none;
    -webkit-appearance: none;
    border-radius: 0.313rem;
    height: 3px;
    width: calc(100% - 40px);
    -moz-appearance: none;
    cursor: pointer;
    background-color: var(--progress-bar-track-unfilled);
}

/* Seekbar thumb \u2014 WebKit */
.progressBar::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 12px;
    height: 12px;
    background-color: var(--accent-color);
    border-radius: 50%;
    cursor: pointer;
    visibility: hidden;
    /* Center the 12px thumb on the 3px track */
    margin-top: -2px;
}

/* Show thumb on hover */
.progressBar:hover::-webkit-slider-thumb {
    visibility: visible;
}

/* Seekbar thumb \u2014 Firefox */
.progressBar::-moz-range-thumb {
    -moz-appearance: none;
    width: 12px;
    height: 12px;
    background-color: var(--accent-color);
    border-radius: 50%;
    cursor: pointer;
    visibility: hidden;
    border: none;
}

/* Show thumb on hover \u2014 Firefox */
.progressBar:hover::-moz-range-thumb {
    visibility: visible;
    -moz-appearance: none;
}

/* Additional styles for the thumb in Firefox */
.progressBar::-moz-range-thumb::before {
    content: ""; /* No content for the pseudo-element */
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 6px; /* Adjust the size of the dot as needed */
    height: 6px; /* Adjust the size of the dot as needed */
    background-color: var(--accent-color); /* Color of the dot */
    border-radius: 50%;
    -moz-appearance: none;
}

#mediaFullScreenResponsiveMd {
    position: absolute;
    bottom: 9.1px;
    right: 0;
    height: 24px;
    width: 30px;
    border-radius: 2px;
}

#mediaFullScreenResponsiveMd:hover {
    background-color: var(--accent-color);
}

#pipButtonResponsiveMd {
    position: absolute;
    bottom: 9.1px;
    right: 0;
    height: 24px;
width: 30px;
}

#pipButtonResponsiveMd:hover {
    background-color: #
}

#bottomRightDivResponsive {
    position: absolute;
    right: 10px;
    bottom: 10px;
}

.mobile #bottomRightDivResponsive {
    bottom: 3px;
}

.mobile #bottomRightDivResponsive .pipButton,
.mobile #bottomRightDivResponsive .playbackRateButtonInitial {
    display: none;
}

.mobile #progressBarResponsive {
    bottom: var(--seekbar-bottom, 33px) !important;
}

#timeDisplayResponsiveMd {
    position: absolute;
    bottom: 8px;
    font-size: 0.875rem;
    left: 126px;
    color: var(--primary-color);
    font-family: Arial, sans-serif;
    padding: 4px;
    border-radius: 2px;
}

#timeDisplayResponsiveMd:hover {
    background-color: var(--accent-color);
}

#forwardSeekInHeightWidth {
    position: absolute;
    bottom: 1px;
    left: 60%;
}

#backwardSeekInHeightWidth {
    position: absolute;
    bottom: 50%;
    right: 60%;
}

#mediaFullScreenResponsiveHeightWidth,
#pipButtonHeightWidth {
    bottom: 0%;
}

#progressBar:hover {
    cursor: pointer;
}

#progressBarResponsive {
    position: absolute;
    bottom: var(--seekbar-bottom, 2.5rem);
    height: 4px;
    left: 20px;
    right: 20px;
}

#playPauseButtonResponsive {
    display:flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    bottom: 45%;
    left: 45%;
    color: var(--primary-color);
    height: 40px;
    width: 40px;
    border-radius: 50%;
}

#initialPlayButton {
    display: flex;
     align-items: center;
    justify-items: center;
}

#progressBarMini {
    position: absolute;
    height: 3px;
    width: 84%;
    bottom: var(--seekbar-bottom, 40px);
    display: none;
}

#bottomRightDivMini {
    display: none;
}

#parentVolumeMini {
    bottom: 0;
}

#pipButtonMini, #fullScreenButtonMini {
    position: absolute;
    bottom: 1px;
}

#bottomRightContainerMini {
    bottom: 40px;
}

#progressBarResponsiveHeightWidth {
    position: absolute;
    bottom: 3.75rem;
    height: 0.25 rem;
    width: 96%;
    right: 2%;
    left: 2%;
}

#timeDisplayHeightWidth {
    position: absolute;
    bottom: 3.75rem;
    font-size: 0.875rem;
    right: 2%;
    color: var(--primary-color);
    font-family: Arial, sans-serif;
    display: none; /* Legacy click timestamp UI \u2013 disabled in favor of hover pill */
}

#bottomRightDiv {
    position: absolute;
    bottom: 10px;
}

/* for screens/video width less <=481 */
#pipButtonResponsive {
    position: absolute;
    bottom: 12px;
    right: 0;
    height: 24px;
    width: 30px;
}

#pipButtonResponsive:hover {
    background-color: var(--accent-color);
}

#mediaFullScreenResponsive {
    bottom: 12px;
    right: 0;
    height: 24px;
    width: 30px;
}

#mediaFullScreenResponsive:hover {
    background-color: var(--accent-color);
}

#play:hover,
#pause:hover {
    background-color: rgba(255, 255, 255, 0.1)
}

#fowardSeekInsecs {
    background-color: transparent;
    border: gray
    cursor: pointer;
    fill: green;
    outline: none;
    width: 24px;
    height: 30px;
    border-radius: 50%;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 0.875;
    position: absolute;
    bottom: 50%; /* Default bottom position */
    left: 54%;
}

#backwardSeekInsecs {
    position: absolute;
    bottom: 50%;
    right: 57%;
    height: 30px;
}

#playPauseButtonResponsiveMd {
    position: absolute;
    bottom: 45%;
    left: 45%;
    color: var(--primary-color);
    height: 2.875rem;
    width: 2.875rem;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

#backwardSeekInsecsMd {
    position: absolute;
    bottom: 10px;
    left: 61px;
    height: 24px;
    width: 30px;
}

#backwardSeekInsecsMd:hover {
    background-color: var(--accent-color);
}

#fowardSeekInsecsMd {
    border: gray;
    cursor: pointer;
    fill: green;
    outline: none;
    height: 24px;
    width: 30px;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 0.875;
    position: absolute;
    bottom: 10px; /* Default bottom position */
    left: 93px;
}

#fowardSeekInsecsMd:hover {
    background-color: var(--accent-color);
}

#mediaFullScreenLandscape {
    background-color: transparent;
    border: gray;
    cursor: pointer;
    fill: white;
    outline: none;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 20%;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
}

#timeControlButtonIncrease {
    background-color: transparent;
    border: gray;
    cursor: pointer;
    outline: none;
    border-radius: 2px;
    width: 30px;
    height: 24px;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    bottom: 10px;
    left: 8.5%;
    font-size: 0.875rem;
}

#timeControlButtonDecrease {
    background-color: transparent;
    border: gray;
    cursor: pointer;
    outline: none;
    border-radius: 2px;
    width: 30px;
    height: 24px;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    bottom: 10px;
    left: 6%;
    font-size: 0.875rem;
}

#timeControlButtonIncrease:hover,
#timeControlButtonDecrease:hover {
    background-color: var(--accent-color);
}

.retryButton, button {
    background: none;
    border: none;
    padding: 0;
    margin: 0;
    font-family: inherit;
    font-size: inherit;
    color: inherit;
    cursor: pointer;
    outline: none; /* Prevents default focus outline */
}

.leftControls {
    display: none;
    position: absolute;
    left: 50px;
    bottom: 10px;
    flex-direction: row;
    align-items: center;
    z-index: 4;
}

.leftControls.mobile {
    display: none;
    position: absolute;
    left: 10px;
    bottom: 3px;
    flex-direction: row;
    align-items: center;
    z-index: 4;
}

.mobileControls {
     display: none;
    position: absolute;
    width: 100%;
    height: 40px;
    bottom: calc(50% - 18px);
    align-items: center;
    justify-content: center;
    left: 0;
}

.parent.initialized .mobileControls {
    display: var(--middle-controls-mobile, flex);
}

.parent.mobile.initialized .title {
    display: none;
}

.parent.mobile.initialized .title-on-demand {
    display: none;
}

.mobile .title {
     display: none;
}

.mobile .playbackRateButtonInitial,
.mobile .pipButton {
    display: none;
}

.pipButton {
display: none;
}

.live-stream {
    --backward-button: none;
    --forward-button: none;
}
    
.mobileControlsButtonsBlock {
    display: none;
    flex-direction: row;
    align-items: center;
}

.mobileControlsButtonsBlock #increaseTimeBtn,
.mobileControlsButtonsBlock #decreaseTimeBtn,
.mobileControlsButtonsBlock #increaseTimeBtn svg,
.mobileControlsButtonsBlock #decreaseTimeBtn svg,
.mobileControlsButtonsBlock .playlistPrevButton,
.mobileControlsButtonsBlock .playlistNextButton,
.mobileControlsButtonsBlock .playlistPrevButton svg,
.mobileControlsButtonsBlock .playlistNextButton svg,
.castButton svg {
    width: 30px !important;
    height: 30px !important;
}

.mobileControlsButtonsBlock #increaseTimeBtn  {
    margin-left: 25px;
}

.mobileControlsButtonsBlock #decreaseTimeBtn {
    margin-right: 25px;
}

.mobileControlsButtonsBlock .playlistPrevButton {
    margin-right: 20px;
}
.mobileControlsButtonsBlock .playlistNextButton {
 margin-left: 20px;
}


.timeDisplay {
    height: var(--button-height);
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--secondary-color);
}

/* All Icons */
.initialPlayBigButton.initialized svg,
#decreaseTimeBtn svg,
#increaseTimeBtn svg,
.parentVolumeDiv svg,
.playbackRateButtonInitial svg,
.ccButton svg,
.pipButton svg,
.fullScreenButton svg,
.resolutionMenuButton svg,
#audioMenuButton svg,
.default-icon,
.castButton svg,
.playlistNextButton svg,
.playlistPrevButton svg,
.playlistButton svg,
.volumeiOSButton {
     width: var(--icon-width);
     height: var(--icon-height);
}

/* All Icon Buttons */
.initialPlayBigButton.initialized:not(.mobile),
#decreaseTimeBtn,
#increaseTimeBtn,
.playbackRateButtonInitial,
.ccButton,
.pipButton,
.fullScreenButton,
.volumeButton,
.resolutionMenuButton,
.audioMenuButton,
.castButton,
.playlistNextButton,
.playlistButton,
.playlistPrevButton,
.default-button {
    width: var(--button-width);
    height: var(--button-height);
    background-color: var(--secondary-color);
    border-radius: var(--border-radius);
    color: var(--primary-color);
    bottom: 10px;
}

.initialplayPauseButtonStyle svg,
#decreaseTimeBtn svg,
#increaseTimeBtn svg,
.parentVolumeDiv svg,
.playbackRateButtonInitial svg,
.ccButton svg,
.pipButton svg,
.fullScreenButton svg,
.volumeiOSButton svg,
.playlistNextButton svg,
.playlistButton svg,
.timeDisplay {
    color: var(--primary-color);
}

.initialPlayBigButton:not(.initialized),
.initialPlayBigButton.initialized.mobile {
    width: var(--button-big-width);
    height: var(--button-big-height);
    border-radius: 50%;
    display: var(--initial-play-button, flex);
    align-items: center;
    justify-content: center;
    left: calc(50% - (var(--button-big-width) / 2));
    bottom: calc(50% - (var(--button-big-height) / 2));
    background-color: transparent;
}

.initialPlayBigButton.initialplayPauseButtonStyle.initialized.showPlayButton {
    display: var(--play-button-initialized, flex) !important;
}

.initialPlayBigButton.initialplayPauseButtonStyle.initialized.showPlayButton.mobile {
    display: var(--mobile-play-button-initialized, flex) !important;
}

.initialPlayBigButton.initialized:not(.mobile) {
    left: 20px;
}

.initialPlayBigButton svg {
    width: var(--icon-big-width);
    height: var(--icon-big-height);
}

.initialPlayBigButton.initialized svg {
    width: var(--icon-width);
    height: var(--icon-height);
}

.initialPlayBigButton:hover,
.initialPlayBigButton.initialized:hover {
    background-color: var(--accent-color);
}

.spinner {
    display: var(--loading-indicator, flex);
    align-items: center;
    justify-content: center;
}

.resolutionMenuButton {
    display: var(--resolution-selector, flex);
    align-items: center;
    justify-content: center;
}

.playlistButton {
    display: var(--playlist, flex);
}

.audioMenuButton {
    display: none;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
}

.initialplayPauseButton.showPlayButton {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mobile hide on hover status */
.mobile .initialPlayBigButton:hover,
.mobile #decreaseTimeBtn:hover,
.mobile #increaseTimeBtn:hover,
.mobile .playbackRateButtonInitial:hover,
.mobile .ccButton:hover,
.mobile .pipButton:hover,
.mobile .fullScreenButton:hover,
.mobile .volumeButton:hover,
.mobile .default-button:hover,
.mobile #audioMenuButton:hover,
.mobile .resolutionMenuButton:hover {
    background-color: transparent !important;
}

#decreaseTimeBtn {
    display: var(--backward-skip-button, flex);
    align-items: center;
    justify-content: center;
}

.playlistNextButton,
.playlistPrevButton,
.playlistButton {
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    background-color: var(--secondary-color);
}

.playlistPrevButton {
display: var(--previous-episode-button, flex);
}

.playlistNextButton {
display: var(--next-episode-button, flex);
}


#increaseTimeBtn {
    display: var(--forward-skip-button, flex);
    align-items: center;
    justify-content: center;
 }

.parentVolumeDiv.initialized {
    display: var(--volume-control, flex);
    align-items: center;
    justify-content: center;
}

.parentVolumeDiv.initialized.mobile {
    display: var(--volume-control-mobile, flex);
    align-items: center;
    justify-content: center;
}

.playbackRateButtonInitial {
    display: var(--playback-rate-button, flex);
    align-items: center;
    justify-content: center;
}

.playbackRate-menu {
    position: absolute;
    right: 0;
    bottom: 50px;
    padding: 6px;
    background-color: var(--primary-color);
    flex-direction: row;
    border-radius: 2px;
}

.ccButton {
    display: none;
}

.audioMenuButtonShow {
    display: var(--audio-track-button, flex);
}

.ccButtonLength {
    display: var(--cc-button, flex);
    align-items: center !important;
justify-content: center !important;

}

.ccButton.disabled {
    display: none;
}

.pip-firefox {
    display: none;
}


.pipButton {
    display: var(--pip-button, flex);
    align-items: center;
    justify-content: center;
}

.fullScreenButton {
    display: var(--full-screen-button, flex);
    align-items: center;
    justify-content: center;
}

.timeDisplay {
    display: var(--time-display, flex);
    align-items: center;
    justify-content: center;
}

.thumbnailSeeking {
   position: absolute;
   z-index: 99;
   bottom: calc(20px + var(--seekbar-bottom, 2.5rem));
   border-color: var(--primary-color);
   border-radius: 3px;
   border-style: solid;
   border-width: 2px 2px 20px 2px; /* bottom border creates the white bar under the frame */
   display: none;
   opacity: 0;
   cursor: pointer;
}

.seekbarPin {
    display: none;
    position: fixed; /* fixed so rect.left + x maps directly to viewport coords */
    width: 2px;
    height: 4px;
    background-color: var(--accent-color);
    border-radius: 1px;
    pointer-events: none;
    z-index: 100;
    /* JS sets left, top, and transform on every mousemove */
}

.thumbnailSeeking.noThumbnail {
   border-color: transparent;
   border-width: 0;
   padding: 0;
   background: none;
   position: absolute;
   /* JS sets left + transform on every mousemove \u2014 do not set them here */
   white-space: nowrap;
}

/* Hide chapter text inside the timestamp pill \u2014 it has no context without a thumbnail frame */
.thumbnailSeeking.noThumbnail .thumbnailChapterDisplay {
    display: none;
}

.thumbnailSeeking.chapters.noThumbnail {
   border-width: 4px;
   bottom: 100px;
}

.thumbnailSeeking.show {
   opacity: 1;
   display: flex;
}

.thumbnailSeeking.chapters.lg.noThumbnail.show .thumbnailChapterDisplay.multi-line {
    bottom: -45px;
}

.thumbnailSeeking.chapters.sm.noThumbnail.show .thumbnailChapterDisplay.multi-line {
    bottom: 25px;
    min-width: auto;
    padding: 2px;
}

.thumbnailSeeking.chapters.md.noThumbnail.show .thumbnailChapterDisplay.multi-line {
    bottom: 25px;
    min-width: auto;
    padding: 4px;
}

.thumbnailSeeking.lg.noThumbnail.show .thumbnailTimeDisplay,
.thumbnailSeeking.md.noThumbnail.show .thumbnailTimeDisplay,
.thumbnailSeeking.sm.noThumbnail.show .thumbnailTimeDisplay {
    padding: 5px 10px;
}


.thumbnailSeeking.show.lg,
.thumbnailSeeking.md.show {
    bottom: calc(30px + var(--seekbar-bottom, 2.5rem));
}

.thumbnailSeeking.show.lg.chapters {
    bottom: calc(60px + var(--seekbar-bottom, 2.5rem));
}

.thumbnailSeeking.show.lg.chapters.noThumbnail {
    bottom: calc(60px + var(--seekbar-bottom, 2.5rem));
}

.thumbnailSeeking.show.sm.chapters.noThumbnail {
    bottom: calc(5px + var(--seekbar-bottom, 2.5rem));
}

.thumbnailSeeking.show.md.chapters.noThumbnail {
    bottom: calc(20px + var(--seekbar-bottom, 2.5rem));
}

.thumbnailSeeking.sm.show {
    bottom: calc(10px + var(--seekbar-bottom, 2.5rem));
}

.thumbnailSeeking.chapters {
   border-width: 2px 2px 20px 2px;
   bottom: calc(60px + var(--seekbar-bottom, 2.5rem));
}

.thumbnailTimeDisplay {
   font-size: 13px;
   text-align: center;
   position: absolute;
   left: 0;
   width: 100%;
   transform: translateX(0);
   bottom: -18px; /* Adjust as needed */
   color: grey;
   z-index: 9;
}

/* noThumbnail / spritesheet-fail state \u2014 pill is the timestamp\u2019s background */
.thumbnailSeeking.noThumbnail .thumbnailTimeDisplay {
   position: static;
   width: auto;
   left: auto;
   transform: none;
   bottom: auto;
   color: #fff;
   font-size: 13px;
   font-weight: 600;
   text-align: center;
   background-color: rgba(0, 0, 0, 0.55);
   padding: 5px 10px;
   border-radius: 4px;
}

.thumbnailChapterDisplay {
    position: absolute;
    bottom: -56px; /* Adjust bottom position to ensure no contact with thumbnailSeeking */
    left: 50%;
    transform: translateX(-50%); /* Center horizontally */
    max-width: var(--thumbnail-max-width); /* Set max-width */
    color: #FFF;
    border-radius: var(--border-radius);
    font-size: 13px;
    text-align: center;
    overflow: hidden; /* Hide overflow text */
    text-overflow: ellipsis; /* Add ellipsis for overflow text */
}

.thumbnailChapterDisplay.noThumbnail {
    position: absolute;
    bottom: -41px;
}

.thumbnailChapterDisplay.single-line {
    white-space: nowrap; /* Prevent text wrapping */
    text-overflow: ellipsis; /* Add ellipsis for overflow text */
}

.thumbnailChapterDisplay.multi-line {
    display: -webkit-box; /* Use a flexbox for multi-line truncation */
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2; /* Clamp to two lines */
    line-clamp: 2; /* Fallback for non-WebKit browsers */
    max-height: calc(3.2em * 2); /* Adjust height to show up to two lines */
    min-width: 157.59px;
}

.thumbnailSeeking.chapters.md.show .thumbnailChapterDisplay.multi-line {
    min-width: 157.59px;
    font-size: 14px;
    bottom: -57px;
    color: var(--primary-color);
}

.thumbnailSeeking.chapters.sm.show .thumbnailTimeDisplay {
    font-size: 10px;
    bottom: -16px;
}

.thumbnailSeeking.chapters.md.show .thumbnailTimeDisplay {
    font-size: 12px;
    bottom: -16px;
}


.thumbnailSeeking.chapters.sm.show .thumbnailChapterDisplay.multi-line {
    min-width: 157.59px;
    font-size: 12px;
    bottom: -40px;
    color: var(--primary-color);
}

.thumbnailSeeking.chapters.sm.show {
    bottom: calc(1.5rem + var(--seekbar-bottom, 2.5rem));
}

.thumbnailSeeking.chapters.md.show {
    bottom: calc(3.8rem + var(--seekbar-bottom, 2.5rem));
}

.chapter-mark {
    position: absolute;
    height: 100%;
    width: 2px;
    cursor: pointer;
}

.chapter-tooltip {
    display: none;
    position: absolute;
    background-color: black;
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    white-space: nowrap;
    transform: translateX(-50%);
}

.chapter-mark:hover .chapter-tooltip {
  display: block;
}

.playlistPrevButton.playlistButtonHidden,
.playlistNextButton.playlistButtonHidden {
  display: none !important;
}

.playlistPrevButton.playlistButtonHidden,
.playlistNextButton.playlistButtonHidden {
  display: none !important;
}
.playlist-panel {
  position: absolute;
  bottom: 60px;
  right: 60px; /* offset from button to avoid hover overlap */
  width: 300px;
  overflow-y: auto;
  background: var(--primary-color);
  border: 1px solid #aaa;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  z-index: 1000;
  padding: 10px;
  display: flex;
  flex-direction: column;
  /* Smooth open/close */
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 0.2s ease, transform 0.2s ease;
  /* Prevent interaction while hidden */
  pointer-events: none;
}

.playlist-panel.open {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}

.playlist-panel.closing {
  opacity: 0;
  transform: translateY(8px);
  pointer-events: none;
}

.playlist-item {
  display: flex;
  gap: 10px;
  margin-bottom: 8px;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  color: #555;
  border: 2px solid transparent; /* \u{1F9E9} Always reserve space for border */
  transition: background 0.2s ease, border 0.2s ease, color 0.2s ease;
}

.playlist-item:hover {
  background: var(--primary-color);
  border: 2px solid var(--accent-color);
}

.playlist-item.selected,
.playlist-item.selected:hover {
  background: var(--accent-color);
  color: var(--primary-color);
}

.thumb {
  width: 80px;
  height: 50px;
  background-size: cover;
  background-position: center;
  border-radius: 4px;
  flex-shrink: 0;
}


.info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.playlist-title {
  font-weight: bold;
  font-size: 14px;
  margin-bottom: 4px;
  text-align: left;
}

.desc {
  font-size: 0.85em;
  text-align: left;          
  word-break: break-word;    
  line-height: 1.4;         
}

.playlist-item-duration {
    font-size: 12px;
    text-align: left;          
  word-break: break-word;    
  line-height: 1.4;         
}

.bottomRightContainer.mobile.initialized .playlist-panel {
z-index: 1600;
right: 0px;
bottom: 46px;
}

.controlsContainer.hasPlaylist .showPlayButton.initialized:not(.mobile) {
    left: 50px;
}

.controlsContainer.hasPlaylist .showPlayButton.initialized:not(.mobile).playlistPrevButtonDisabledByCSS {
    left: 20px;
}


.controlsContainer.hasPlaylist .leftControls {
    left: 20px;
}

.controlsContainer.hasPlaylist .leftControls .playlistNextButton {
    margin-left: 30px;
}

.controlsContainer.hasPlaylist #nextButtonMd {
    margin-left: 40px;
}

.controlsContainer.hasPlaylist .showPlayButton.initialized:not(.mobile) .playlistPrevButtonDisabledByCSS {
    left: 20px;
    
}

.forwardRewindControlsWrapper.playlistPrevButtonDisabledByCSS,
.forwardRewindControlsWrapper.playlistNextButtonDisabledByCSS {

}

.forwardRewindControlsWrapper.playlistNextButtonDisabledByCSS .playlistButtonVisible {
    margin-right: 30px;
}

.forwardRewindControlsWrapper.playlistPrevButtonDisabledByCSS .playlistButtonVisible {
    margin-right: 0px !important;
}

.controlsContainer.hasPlaylist .leftControls .playlistNextButton {
    margin-left: 30px;
}

.forwardRewindControlsWrapper.playlistPrevButtonDisabledByCSS.playlistNextButtonDisabledByCSS {
    margin-right: 0px;
    margin-left: 30px;
}

.mobileControls.nextButtonDisabledMobile,
.mobileControls.forwardSkipButtonHidden {
    left: -24px;
}

.mobileControls.prevButtonDisabledMobile,
.mobileControls.rewindBackButtonHidden {
    left: 24px;
}

.mobileControls.forwardSkipButtonHidden.rewindBackButtonHidden.nextButtonDisabledMobile {
    left: -24px;
    bottom: 84px;
}

.mobileControls.nextButtonDisabledMobile.prevButtonDisabledMobile {
    left: 0px;
}

// shoppable content

// .hotspot {
//   position: absolute;
//   width: 32px;
//   height: 32px;
//   background: transparent;
//   border-radius: 50%;
//   z-index: 1000;
//   display: flex;
//   align-items: center;
//   justify-content: center;
//   cursor: pointer;
// }

// .hotspot .hotspot-dot {
//   position: relative;
//   width: 12px;
//   height: 12px;
//   background-color: var(--accent-color);
//   border-radius: 50%;
//   z-index: 2;
//   box-shadow: 0 0 0 2px #fff;
// }

// /* Pulsating rings */
// .hotspot .hotspot-dot::before,
// .hotspot .hotspot-dot::after {
//   content: '';
//   position: absolute;
//   left: 50%;
//   top: 50%;
//   width: 16px;  /* starts outside the 12px dot */
//   height: 16px;
//   border: 2px solid var(--accent-color);
//   border-radius: 50%;
//   transform: translate(-50%, -50%) scale(1);
//   animation: pulse-ring 3.4s infinite ease-out;
//   z-index: 1;
// }

// @keyframes pulse-ring {
//   0% {
//     transform: translate(-50%, -50%) scale(1);
//     opacity: 0.7;
//   }
//   100% {
//     transform: translate(-50%, -50%) scale(2);
//     opacity: 0;
//   }
// }

.hotspot {
  position: absolute;
  width: 32px;
  height: 32px;
  background: transparent;
  border-radius: 50%;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.hotspot .hotspot-dot {
  position: relative;
  width: 12px;
  height: 12px;
  background-color: var(--accent-color);
  border-radius: 50%;
  z-index: 2;
  box-shadow: 0 0 0 2px #fff;
}

/* Pulsating rings */
.hotspot .hotspot-dot::after {
  content: '';
  position: absolute;
  left: 50%;
  top: 50%;
  width: 16px;
  height: 16px;
  border: 2px solid var(--primary-color);
  border-radius: 50%;
  transform: translate(-50%, -50%) scale(1);
  transform-origin: center;
  animation: pulse-ring 1s infinite ease-out;
  will-change: transform, opacity;
  z-index: 1;
  opacity: 0.6;
}
.hotspot:hover .hotspot-dot::after,
.hotspot:focus .hotspot-dot::after,
.hotspot .hotspot-dot:hover::after,
.hotspot .hotspot-dot:focus::after {
  animation-play-state: paused;
}

@keyframes pulse-ring {
  0% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0.6;
  }
  100% {
    transform: translate(-50%, -50%) scale(1.4); /* ends around 33.6px */
    opacity: 0;
  }
}

.hotspot-tooltip {
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.2s;
  z-index: 1300;
  position: absolute;
  background: #222;
  color: #fff;
  padding: 8px 14px;
  border-radius: 6px;
  font-size: 0.97em;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(0,0,0,0.18);
}
  
.hotspot:focus .hotspot-tooltip {
  opacity: 1;
}

.cartProduct {
  position: relative;
}

@keyframes cart-dance {
  0% { transform: scale(1) rotate(0deg); }
  20% { transform: scale(1.2) rotate(-10deg); }
  40% { transform: scale(0.9) rotate(10deg); }
  60% { transform: scale(1.1) rotate(-8deg); }
  80% { transform: scale(1.05) rotate(8deg); }
  100% { transform: scale(1) rotate(0deg); }
}
.cart-dance {
  animation: cart-dance 0.5s cubic-bezier(.4,2,.6,1);
}

.post-play-overlay {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(8px);
  background: rgba(0,0,0,0.35);
}
.post-play-products-row {
  display: flex;
  flex-direction: row;
  gap: 32px;
  align-items: center;
  justify-content: center;
}
.post-play-overlay .cartProduct {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.10);
  min-width: 120px;
  max-width: 180px;
  cursor: pointer;
}
.post-play-overlay button {
  margin-top: 32px;
  padding: 12px 32px;
  font-size: 1.1em;
  border-radius: 8px;
  border: none;
  background: var(--primary-color, #ff4081);
  color: #fff;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,0,0,0.10);
  transition: background 0.2s;
}
.post-play-overlay button:hover {
  background: #e73370;
}

.cartSidebarOpen-progress-bar.progressBar.initialized:not(.bottomRightDivMedium) {
  width: calc(100% - var(--shoppable-sidebar-width) - 40px) !important;
}



.bottomRightContainer.initialized.cartSidebarOpen-bottom-right-div {
  right: calc(var(--shoppable-sidebar-width) + 20px) !important;
}

.bottomRightContainer.initialized.cartSidebarOpen-bottom-right-div.mobile,
.bottomRightContainer.initialized.cartSidebarOpen-bottom-right-div.medium {
    right: 10px !important;
}

.progressBar.mobile.initialized.cartSidebarOpen-progress-bar {
    width: 100% !important;
}

.cartSidebarProducts {
flex:1;
overflow-y:auto;
padding:0 16px;
}

.cartSidebarProducts.mobile {
padding: 0 !important;
}

.cartProduct {
display:flex;
padding: 10px;
cursor:pointer;
align-items:center;
justify-content:center;
position: relative;
}

.cartProduct .cartSidebarProducts.mobile {
margin-bottom: 6px;
}

.mobileControlsButtonsBlock .decreaseTimeBtn.forwardSkipButtonHidden {
    margin-right: 70px !important;
}

.mobileControlsButtonsBlock .increaseTimeBtn.rewindBackButtonHidden {
    margin-left: 70px !important;
}

.product-hover-overlay.post-play {
  border-radius: 8px 8px 0px 0px;
  padding: 10px;
  inset:0;
}
  .product-hover-overlay {
  border-radius: 8px;
  padding: 10px;
  inset:10px;
}
`;function ut(e){let t=e.wrapper.querySelector("video"),i=document.fullscreenElement??document.webkitFullscreenElement??document.mozFullScreenElement??document.msFullscreenElement;i&&i===t?e.progressBar.style.height="1.875rem":e.progressBar.style.backgroundColor=""}function Lr(e){function t(){e.video.readyState>=1?j(e):e.video.addEventListener("loadedmetadata",()=>j(e),{once:!0})}m.addEventListener("fullscreenchange",()=>{document.fullscreenElement&&K(e),t()}),m.addEventListener("fullscreenchange",()=>ut(e)),m.addEventListener("webkitfullscreenchange",()=>{ut(e),t()}),m.addEventListener("mozfullscreenchange",()=>{ut(e),t()}),m.addEventListener("MSFullscreenChange",()=>{ut(e),t()})}function _r(e,t,i){e.video.playbackRate=t,e.lastClickedPlaybackRateButton!==null&&(e.lastClickedPlaybackRateButton.style.fontWeight="normal",e.lastClickedPlaybackRateButton.classList.remove("active")),i.classList.add("active"),e.playbackRateButton.textContent=`${t}x`,e.playbackRateButton.title=`${t}x`,e.lastClickedPlaybackRateButton=i,e.playbackRateDiv.style.display="none"}function Ar(e){if(e!==null){let t=e.trim().split(" ");if(t.length===1&&!isNaN(parseFloat(t[0])))return t[0]}return null}var Rr="000000",Vr=function(){let e=Math.random().toString(36).replace("0.","").slice(0,6);return Rr.slice(e.length)+e};function Ws(){return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,function(e){let t=Math.random()*16|0;return(e==="x"?t:t&3|8).toString(16)})}var Se=function(){return Ws()},js=function(){return(Rr+(Math.random()*Math.pow(36,6)<<0).toString(36)).slice(-6)},Ut=e=>{if(!e)return["localhost","localhost"];try{let t=new URL(e).hostname,i=t.split("."),r=i.length>=2?i.slice(-2).join("."):t;return[t,r]}catch{}return["localhost","localhost"]},re=e=>Ut(e)[0],Wt=e=>Ut(e)[1],$s=e=>{if(e&&e.nodeName)return e.uniqueId??(e.uniqueId=Vr());try{let t=document.querySelector(e);return t&&!t.uniqueId&&(t.uniqueId=e),t?.uniqueId??e}catch{}return e},Zs=e=>{let t=null;e&&e.nodeName!==void 0?(t=e,e=$s(t)):t=document.querySelector(e);let i=t?.nodeName?.toLowerCase()||"";return[t,e,i]},Pr=e=>{let t=null;if(e&&e.nodeName!==void 0)return e.elementId??(e.elementId=js()),e.elementId;try{t=document.querySelector(e)}catch{}return t&&!t.elementId&&(t.elementId=e),t?.elementId??e};function Ks(...e){return e.reduce((t,i)=>{for(let[r,a]of Object.entries(i))a!==void 0&&(t[r]=a);return t},{})}function mt(e,t,i=1){e[t]=(e[t]??0)+i}function Gs(e,t){let{beaconDomain:i}=t;return`https://${e??"collector"}.${i??"anlytix.io"}`}function Or(){let e=navigator.doNotTrack??window.doNotTrack??navigator.msDoNotTrack;return e==="1"||e==="yes"}var Qs=class{constructor(){this.events={}}on(e,t){this.events[e]||(this.events[e]=[]),this.events[e].push(t)}off(e,t){this.events[e]&&(this.events[e]=this.events[e].filter(i=>i!==t))}emit(e,t){this.events[e]&&this.events[e].forEach(i=>{i(t)})}},me={now:function(){return typeof Date.now=="function"?Date.now():new Date().getTime()}},ct={isPerformanceAvailable:function(){let e=window.performance;return e&&e.timing!==void 0},getDomContentLoadedEnd:function(){let e=window.performance?.timing;return e?e.domContentLoadedEventEnd:null},getNavigationStartTime:function(){let e=window.performance?.timing;return e?e.navigationStart:null}};function Ys(e){if(!e)return{};let t=ct.getNavigationStartTime(),{loading:i,trequest:r,tfirst:a,tload:s,total:o}=e,l=i?i.start:r,u=i?i.first:a,n=i?i.end:s;return{bytesLoaded:o,requestStart:Math.round(t+l),responseStart:Math.round(t+u),responseEnd:Math.round(t+n)}}var Xs=()=>{let e=navigator;return(e?.connection??e?.mozConnection??e?.webkitConnection)?.type},Js=()=>{switch(Xs()){case"cellular":return"cellular";case"ethernet":return"wired";case"wifi":return"wifi";case void 0:break;default:return"other"}},xs=["x-cdn","content-type","content-length","last-modified","server","x-request-id","cf-ray","x-amz-cf-id","x-akamai-request-id"];function eo(e){let t={},i=new Set(xs.map(r=>r.toLowerCase()));return!e||typeof e!="string"?{}:(e.trim().split(/[\r\n]+/).forEach(r=>{let[a,...s]=r.split(": "),o=s.join(": ");a&&i.has(a.toLowerCase())&&(t[a]=o)}),t)}var dt=function(e){if(e&&typeof e.getAllResponseHeaders=="function")return eo(e.getAllResponseHeaders())},to={convertSecToMs:function(e){return Math.floor(1e3*e)},isolateHostAndDomainName:Ut,fetchDomain:Wt,fetchHost:re,generateIdToken:Vr,buildUUID:Se,now:me.now},io=class{constructor(e,t){this.params=e,this.emitter=t,e.allowRebufferTracking||this.initEventListeners()}initEventListeners(){this.emitter.on("pulseStart",e=>this.processBufferMetrics(e)),this.emitter.on("buffering",e=>this.handleBufferingStart(e)),this.emitter.on("buffered",e=>this.handleBufferingEnd(e)),this.emitter.on("configureView",()=>this.resetTimer())}handleBufferingStart(e){this.startTimer||(this.params.data.view_rebuffer_count=(this.params.data.view_rebuffer_count??0)+1,this.startTimer=e.viewer_timestamp)}handleBufferingEnd(e){this.processBufferMetrics(e),this.startTimer=void 0}processBufferMetrics(e){if(this.startTimer){let t=e.viewer_timestamp-this.startTimer;this.params.data.view_rebuffer_duration=(this.params.data.view_rebuffer_duration??0)+t,this.startTimer=e.viewer_timestamp,this.params.data.view_rebuffer_duration>3e5&&this.delayBufferDestroyer()}this.params.data.view_watch_time&&this.params.data.view_watch_time>=0&&this.params.data.view_rebuffer_count&&this.params.data.view_rebuffer_count>0&&(this.params.data.view_rebuffer_frequency=this.params.data.view_rebuffer_count/this.params.data.view_watch_time,this.params.data.view_rebuffer_duration&&(this.params.data.view_rebuffer_percentage=this.params.data.view_rebuffer_duration/this.params.data.view_watch_time))}delayBufferDestroyer(){this.params.dispatch("viewCompleted"),this.params.filterData("viewCompleted"),this.params?.userConfigData?.actionableData?.debug}resetTimer(){this.startTimer=void 0}},ro=class{constructor(e,t){this.waiter=e,this.emitter=t,this.isWaiting=!1,this.lastCheckedTime=null,this.lastPlayheadTime=null,this.lastUpdatedTime=null,!e.allowRebufferTracking&&!e.disablePlayheadRebufferTracking&&this.setupEventListeners()}setupEventListeners(){this.emitter.on("pulseStart",e=>this.checkForBuffering(e)),this.emitter.on("pulseEnd",e=>this.handleBufferingEnd(e)),this.emitter.on("seeking",e=>this.handleBufferingEnd(e)),this.emitter.on("viewCompleted",e=>this.handleBufferingEnd(e))}checkForBuffering(e){if(this.shouldResetBuffering()){this.handleBufferingEnd(e);return}if(this.lastCheckedTime===null){this.startBuffering(e.viewer_timestamp);return}this.isPlayheadStuck()?(e.viewer_timestamp-(this.lastUpdatedTime??0)>=1e3&&!this.isWaiting&&this.triggerBuffering(e),this.lastCheckedTime=e.viewer_timestamp):this.handleBufferingEnd(e,!0)}shouldResetBuffering(){return!!this.waiter.gripper?.videoDragged||!this.waiter.playheadProgressing}isPlayheadStuck(){return this.lastPlayheadTime===this.waiter.data.player_playhead_time}startBuffering(e){this.lastCheckedTime=e,this.lastPlayheadTime=this.waiter.data.player_playhead_time,this.lastUpdatedTime=e}triggerBuffering(e){this.isWaiting=!0,this.waiter.dispatch("buffering",{viewer_timestamp:this.lastUpdatedTime})}handleBufferingEnd(e,t=!1){if(this.isWaiting)this.endBuffering(e);else{if(this.lastCheckedTime===null)return;this.hasSignificantProgress(e)&&this.recalibrateBuffering(e)}t?this.startBuffering(e?.viewer_timestamp??0):this.clearBufferingState()}endBuffering(e){this.isWaiting=!1,this.waiter.dispatch("buffered",{viewer_timestamp:e?.viewer_timestamp})}hasSignificantProgress(e){let t=this.waiter.data.player_playhead_time-(this.lastPlayheadTime??0),i=(e?.viewer_timestamp??0)-(this.lastUpdatedTime??0);return t>0&&i-t>250}recalibrateBuffering(e){let t=this.waiter.data.player_playhead_time-(this.lastPlayheadTime??0),i=(e?.viewer_timestamp??0)-(this.lastUpdatedTime??0);this.waiter.dispatch("buffering",{viewer_timestamp:this.lastUpdatedTime}),this.waiter.dispatch("buffered",{viewer_timestamp:(this.lastUpdatedTime??0)+i-t}),this.lastCheckedTime=null}clearBufferingState(){this.lastCheckedTime=null,this.lastPlayheadTime=null,this.lastUpdatedTime=null}},ao=class{constructor(e,t){this.accuracy=e,this.eventEmitter=t,this.hasErrorOccurred=!1,this.setupEventListeners()}setupEventListeners(){this.eventEmitter.on("configureView",()=>{this.hasErrorOccurred=!1}),this.eventEmitter.on("error",e=>{try{e?.player_error_code||e.player_error_message||e.player_error_context?(this.accuracy.data.player_error_code=e.player_error_code??"",this.accuracy.data.player_error_message=e.player_error_message??"",this.accuracy.data.player_error_context=e.player_error_context??"",this.hasErrorOccurred=!0):(delete this.accuracy.data.player_error_code,delete this.accuracy.data.player_error_message,delete this.accuracy.data.player_error_context)}catch{this.accuracy.userConfigData?.actionableData?.debug,this.hasErrorOccurred=!0}})}},Fr="FastPixData",so=function(e){let t=document.cookie.split(";");for(let i of t){let r=i.trim();if(r.startsWith(e+"=")){let a=decodeURIComponent(r.substring(e.length+1)),s={};return a.split("&").forEach(o=>{let[l,u]=o.split("=");s[l]=u}),s}}return{}},oo=(e,t,i)=>{let r=new Date(Date.now()+i*864e5).toUTCString();document.cookie=`${e}=${t}; expires=${r}; path=/`},Nr=()=>so(Fr)??{},qr=e=>{let t=`fpviid=${e?.fpviid}&fpsanu=${e?.fpsanu}&snid=${e?.snid}&snepti=${e?.snepti}&snst=${e.snst}`;oo(Fr,t,365)},no=()=>{let e=Nr(),t=e.fpviid!=="undefined"&&e.fpviid?e.fpviid:Se(),i=e.fpsanu!=="undefined"&&e.fpsanu?e.fpsanu:Math.random();return e.fpviid=t,e.fpsanu=i,qr(e),{fastpix_viewer_id:e.fpviid,fastpix_sample_number:e.fpsanu}},Mr=async(e,t,i,r)=>{try{i&&navigator.sendBeacon?.(e,t)&&r();try{let a=await fetch(e,{method:"POST",body:t,headers:{"Content-Type":"text/plain"}});return r(null,a.ok?null:"Error")}catch(a){let s=a instanceof Error?a.message:"Fetch error";return r(null,s)}}catch(a){let s=a instanceof Error?a.message:"Fetch error";return r(null,s)}},lo=class{constructor(e,t){this.postApiUrl=e,this.actionableData=t,this.eventStack=[],this.checkPostData=!1,this.callPostTimer=null,this.destroyed=!1}scheduleEvent(e){let t={...e};this.eventStack.push(t),this.destroyed=!1,this.callPostTimer||this.triggerBeaconDispatch()}processEventQueue(){this.emitBeaconQueue(),this.triggerBeaconDispatch()}destroy(e){this.destroyed=!0,e?this.purgeBeaconQueue():this.processEventQueue(),this.callPostTimer&&clearTimeout(this.callPostTimer)}purgeBeaconQueue(){let e=this.eventStack.length-200,t=this.eventStack.slice(e>0?e:0),i=this.generatePayload(t);this.actionableData?.actionableData?.respectDoNotTrack||Mr(this.postApiUrl,i,!0,()=>{})}emitBeaconQueue(){if(!this.checkPostData){let e=this.eventStack.slice(0,200),t=this.generatePayload(e),i=me.now();this.eventStack=this.eventStack.slice(200),this.checkPostData=!0,this.actionableData?.actionableData?.respectDoNotTrack||Mr(this.postApiUrl,t,!1,(r,a)=>{a&&(this.eventStack=e.concat(this.eventStack)),this.chunkTimer=me.now()-i,this.checkPostData=!1})}}triggerBeaconDispatch(){this.callPostTimer&&clearTimeout(this.callPostTimer),this.destroyed||(this.callPostTimer=setTimeout(()=>{this.eventStack.length&&this.emitBeaconQueue(),this.triggerBeaconDispatch()},1e4))}generatePayload(e){let t={transmission_timestamp:Math.round(me.now())};this.chunkTimer&&(t.rtt_ms=Math.round(this.chunkTimer));let i=a=>({payload:JSON.stringify({metadata:t,events:a})}),{payload:r}=i(e);return r}},Dr={ad:"ad",aggregate:"ag",api:"ai",application:"ap",architecture:"ar",asset:"as",autoplay:"au",avg:"av",beacon:"be",bitrate:"bi",break:"bk",browser:"br",bytes:"by",cancel:"ca",codec:"cc",code:"cd",counter:"ce",config:"cf",category:"cg",changed:"ch",connection:"ci",clicked:"ck",canceled:"cl",custom:"cm",cdn:"cn",count:"co",complete:"cp",creative:"cr",continuous:"cs",content:"ct",current:"cu",context:"cx",device:"de",downscaling:"dg",drm:"dm",domain:"dn",downscale:"do",dropped:"dr",duration:"du",errorcode:"ec",end:"ed",edge:"eg",engine:"ei",embed:"em",encoding:"eo",expiry:"ep",error:"er",experiments:"es",errortext:"et",event:"ev",experiment:"ex",failed:"fa",first:"fi",fullscreen:"fl",format:"fm",fastpix:"fp",frequency:"fq",frame:"fr",fps:"fs",family:"fy",has:"ha",holdback:"hb",hostname:"hn",host:"ho",headers:"hs",height:"ht",id:"id",internal:"il",instance:"in",ip:"ip",is:"is",init:"it",key:"ke",labeled:"lb",loaded:"ld",level:"le",live:"li",language:"ln",load:"lo",lists:"ls",latency:"lt",max:"ma",media:"me",manifest:"mf",mime:"mi",midroll:"ml",min:"mn",model:"mo",manufacturer:"mr",message:"ms",name:"na",newest:"ne",number:"nu",on:"on",os:"os",page:"pa",playback:"pb",producer:"pd",preroll:"pe",percentage:"pg",playhead:"ph",plugin:"pi",player:"pl",program:"pm",playing:"pn",poster:"po",property:"pp",preload:"pr",position:"ps",part:"pt",paused:"pu",played:"py",ratio:"ra",rebuffer:"rb",requested:"rd",rate:"re",resolution:"rl",remote:"rm",rendition:"rn",response:"rp",request:"rq",requests:"rs",sample:"sa",sdk:"sd",seek:"se",skipped:"sk",stream:"sm",session:"sn",source:"so",startup:"sp",sequence:"sq",series:"sr",start:"st",sub:"su",server:"sv",software:"sw",tag:"ta",tech:"tc",text:"te",target:"tg",throughput:"th",time:"ti",total:"tl",to:"to",timestamp:"tp",title:"tt",type:"ty",upscaling:"ug",universal:"un",upscale:"up",url:"ur",user:"us",used:"ud",variant:"va",video:"vd",view:"ve",viewer:"vi",version:"vn",viewed:"vw",watch:"wa",waiting:"wg",width:"wt",workspace:"ws"},uo=function(e){let t={};for(let i in e){let r=i.split("_"),a="";r.forEach(s=>{Dr[s]?a+=Dr[s]:Number(s)&&Math.floor(Number(s))===Number(s)?a+=s:a+=`_${s}_`}),t[a]=e[i]}return t},po=["workspace_id","view_id","view_sequence_number","player_sequence_number","beacon_domain","player_playhead_time","viewer_timestamp","event_name","video_id","player_instance_id"],co=["player_is_paused","player_width","player_height","player_autoplay_on","player_preload_on","player_is_fullscreen","video_source_height","video_source_width","video_source_url","video_source_domain","video_source_hostname","video_source_duration","video_poster_url","player_language_code","view_dropped_frame_count"],mo=["viewBegin","error","ended","viewCompleted"],Ir={},ho=class{constructor(e={},t="",i={}){this.fp=e,this.tokenId=t,this.actionableData=i??{},this.debug=this.actionableData?.debug??!1,this.sampleRate=this.actionableData?.sampleRate??1,this.disableCookies=this.actionableData?.disableCookies??!1,this.respectDoNotTrack=this.actionableData?.respectDoNotTrack??!1,this.eventQueue=new lo(Gs(this.tokenId,this.actionableData),this.actionableData),this.previousBeaconData=null,this.sdkPageDetails={viewer_connection_type:Js(),page_url:typeof window<"u"?window?.location?.href:""};let r=typeof document<"u";this.userData=this.disableCookies||!r?{}:no()}sendData(e,t){if(!e||!t?.view_id||this.shouldRespectDoNotTrack(e)||!this.validateEventData(t)||!this.tokenId&&this.debug&&!this.actionableData?.actionableData?.beaconCollectionDomain)return;let i=this.prepareEventData(e,t);i=Object.fromEntries(Object.entries(i).filter(([r,a])=>a!==void 0&&!Number.isNaN(a))),this.eventQueue.scheduleEvent(i),e==="viewCompleted"?this.eventQueue.destroy(!0):mo.includes(e)&&this.eventQueue.processEventQueue()}shouldRespectDoNotTrack(e){return this.respectDoNotTrack&&Or()?(this.debug,!0):!1}validateEventData(e){return!e||typeof e!="object"?(this.debug,!1):!0}prepareEventData(e,t){let i=this.disableCookies||typeof document>"u"?{}:this.updateCookies(),r=Ks(this.sdkPageDetails,t,i,this.userData,{event_name:e,workspace_id:this.tokenId});return uo(this.cloneBeaconData(e,r))}destroy(){this.eventQueue.destroy(!1)}cloneBeaconData(e,t){let i={};if(e==="viewBegin"||e==="viewCompleted"?(i=Object.assign(i,t),e==="viewCompleted"&&(this.previousBeaconData=null),this.previousBeaconData=i):(po.forEach(r=>i[r]=t[r]),Object.assign(i,this.getTrimmedState(t)),["requestCompleted","requestFailed","requestCanceled"].includes(e)&&Object.entries(t).forEach(([r,a])=>{r.startsWith("request")&&(i[r]=a)}),e==="variantChanged"&&Object.entries(t).forEach(([r,a])=>{r.startsWith("video_source")&&(i[r]=a)}),this.previousBeaconData=i),e==="viewCompleted"){let r={};return Object.keys(i).forEach(a=>{co.includes(a)||(r[a]=i[a])}),this.previousBeaconData=r,r}return i}getTrimmedState(e){if(JSON.stringify(this.previousBeaconData)!==JSON.stringify(e)){let t={};for(let i in e)e[i]!==Ir[i]&&(t[i]=e[i]);return Ir=e,t}}updateCookies(){if(typeof document>"u")return{};let e=Nr(),t=Date.now();return(!e.fpviid||!e.fpsanu||e.fpviid==="undefined"||e.fpsanu==="undefined")&&(e.fpviid=Se(),e.fpsanu=Math.random()),(!e.snst||!e.snid||e.snid==="undefined"||e.snst==="undefined"||t-parseInt(e.snst)>864e5)&&(e.snst=t,e.snid=Se()),e.snepti=t+15e5,qr(e),{session_id:e.snid,session_start:e.snst,session_expiry_time:e.snepti}}},fo=class{constructor(e,t){this.playbackTimeTrackerLastPosition=-1,this.prevPlaybackTime=me.now(),this.playbackProgressCallback=null,this.prevProgressPlaybackTime=0,this.emitter=t,this.playback=e,this.initialize()}initialize(){this.emitter.on("playing",()=>{this.initiatePlaybackMonitoring()}),this.emitter.on("seeked",()=>{this.initiatePlaybackMonitoring()}),this.emitter.on("seeking",()=>{this.stopPlaybackMonitoring()}),this.emitter.on("pulseEnd",()=>{this.stopPlaybackMonitoring()}),this.emitter.on("configureView",()=>{this.resetState()})}resetState(){this.playbackTimeTrackerLastPosition=-1,this.prevPlaybackTime=me.now(),this.playbackProgressCallback=null,this.prevProgressPlaybackTime=0}initiatePlaybackMonitoring(){this.playbackProgressCallback===null&&(this.playbackProgressCallback=this.refreshPlaybackMonitoring(),this.playbackTimeTrackerLastPosition=this.playback.data.player_playhead_time,this.emitter.on("pulseStart",()=>{this.refreshPlaybackMonitoring()}))}stopPlaybackMonitoring(){this.playbackProgressCallback!==null&&(this.refreshPlaybackMonitoring(),this.playbackProgressCallback=null,this.playbackTimeTrackerLastPosition=-1,this.prevProgressPlaybackTime=0)}refreshPlaybackMonitoring(){let e=this.playback.data.player_playhead_time,t=me.now(),i=-1;return this.playbackTimeTrackerLastPosition>=0&&e>this.playbackTimeTrackerLastPosition&&(i=e-this.playbackTimeTrackerLastPosition),i>0&&i<=1e3&&mt(this.playback.data,"view_content_playback_time",i),this.playbackTimeTrackerLastPosition=e,this.prevPlaybackTime=t,()=>{}}},yo=class{constructor(e,t){this.timer=e,this.emitter=t,this.initializeEventListeners()}initializeEventListeners(){this.emitter.on("timeupdate",e=>this.handleCurrentPosition(e)),this.emitter.on("pulseStart",e=>this.handleCurrentPosition(e)),this.emitter.on("pulseEnd",e=>this.handleCurrentPosition(e))}handleMaxPosition(){this.timer.data.view_max_playhead_position=this.timer.data.view_max_playhead_position===void 0?this.timer.data.player_playhead_time:Math.max(this.timer.data.view_max_playhead_position,this.timer.data.player_playhead_time)}handleCurrentPosition(e){if(e?.player_playhead_time!==void 0)this.timer.data.player_playhead_time=e.player_playhead_time,this.handleMaxPosition();else if(this.timer.fetchPlayheadTime){let t=this.timer.fetchPlayheadTime();t!==void 0&&(this.timer.data.player_playhead_time=t,this.handleMaxPosition())}}},bo=class{constructor(e,t){this.playheadProgressing=!1,this.pulseIntervalId=null,this.handlePlay=()=>{this.callPulseInterval()},this.handlePlaying=()=>{this.pulse.playheadProgressing=!0,this.callPulseInterval()},this.handleSeeked=()=>{this.pulse.data?.player_is_paused?this.endPulseInterval():this.callPulseInterval()},this.handleTimeUpdate=()=>{this.pulseIntervalId&&this.pulse.dispatch("pulseStart")},this.pulse=e,this.emitter=t,this.initialize()}callPulseInterval(){this.pulseIntervalId||(this.pulse.dispatch("pulseStart"),this.pulseIntervalId=setInterval(()=>{this.pulse.dispatch("pulseStart")},25))}endPulseInterval(){this.pulse.playheadProgressing=!1,this.pulseIntervalId&&(clearInterval(this.pulseIntervalId),this.pulse.dispatch("pulseEnd"),this.pulseIntervalId=null)}initialize(){this.emitter.on("play",this.handlePlay),this.emitter.on("playing",this.handlePlaying),this.emitter.on("viewBegin",this.callPulseInterval.bind(this)),this.emitter.on("buffering",this.callPulseInterval.bind(this)),this.emitter.on("ended",this.endPulseInterval.bind(this)),this.emitter.on("pause",this.endPulseInterval.bind(this)),this.emitter.on("viewCompleted",this.endPulseInterval.bind(this)),this.emitter.on("error",this.endPulseInterval.bind(this)),this.emitter.on("seeked",this.handleSeeked.bind(this)),this.emitter.on("timeupdate",this.handleTimeUpdate.bind(this))}},go=class{constructor(e,t){this.totalLatency=0,this.totalBytes=0,this.totalTime=0,this.requestCount=0,this.processedChunks=0,this.failedRequests=0,this.canceledRequests=0,this.req=e,this.emitter=t,this.initializeEventListeners()}initializeEventListeners(){this.emitter.on("requestCompleted",e=>this.handleRequestCompleted(e)),this.emitter.on("requestFailed",()=>this.handleRequestFailed()),this.emitter.on("requestCanceled",()=>this.handleRequestCanceled())}handleRequestCompleted(e){let t=e?.request_start??0,i=e?.request_response_start??0,r=e?.request_response_end??0,a=e?.request_bytes_loaded??0,s=i-t,o=r-(i??t);if(this.requestCount++,o>0&&a>0){this.processedChunks++,this.totalBytes+=a,this.totalTime+=o;let l=a/o*8e3;this.req.data.view_min_request_throughput=Math.min(this.req.data.view_min_request_throughput??1/0,l),this.req.data.view_avg_request_throughput=this.totalBytes/this.totalTime*8e3,this.req.data.view_request_count=this.requestCount,s>0&&(this.totalLatency+=s,this.req.data.view_max_request_latency=Math.max(this.req.data.view_max_request_latency??0,s),this.req.data.view_avg_request_latency=this.totalLatency/this.processedChunks)}}handleRequestFailed(){this.requestCount++,this.failedRequests++,this.req.data.view_request_count=this.requestCount,this.req.data.view_request_failed_count=this.failedRequests}handleRequestCanceled(){this.requestCount++,this.canceledRequests++,this.req.data.view_request_count=this.requestCount,this.req.data.view_request_canceled_count=this.canceledRequests}},vo=class{constructor(e,t){this.state={previousPlayheadPosition:-1,prevPlayerWidth:-1,prevVideoWidth:-1,prevPlayerHeight:-1,prevVideoHeight:-1},this.scaler=e,this.emitter=t,this.initialize()}resetPlayheadPosition(){this.state.previousPlayheadPosition=-1}handleEvent(e){this.emitter.on(e,()=>{let{state:t,scaler:i}=this;if(t.previousPlayheadPosition>=0&&i.data.player_playhead_time>=0&&t.prevPlayerWidth>=0&&t.prevVideoWidth>0&&t.prevPlayerHeight>=0&&t.prevVideoHeight>0){let r=i.data.player_playhead_time-t.previousPlayheadPosition;if(r<0)return this.resetPlayheadPosition();let a=Math.min(t.prevPlayerWidth/t.prevVideoWidth,t.prevPlayerHeight/t.prevVideoHeight),s=Math.max(0,a-1),o=Math.max(0,1-a);i.data.view_max_upscale_percentage=Math.max(i.data.view_max_upscale_percentage??0,s),i.data.view_max_downscale_percentage=Math.max(i.data.view_max_downscale_percentage??0,o),i.data.view_total_content_playback_time=(i.data.view_total_content_playback_time??0)+r,i.data.view_total_upscaling=(i.data.view_total_upscaling??0)+s*r,i.data.view_total_downscaling=(i.data.view_total_downscaling??0)+o*r}this.resetPlayheadPosition()})}setPlayheadPosition(e){this.emitter.on(e,()=>{let{state:t,scaler:i}=this;t.previousPlayheadPosition=i.data.player_playhead_time,t.prevPlayerWidth=i.data.player_width,t.prevPlayerHeight=i.data.player_height,t.prevVideoWidth=i.data.video_source_width,t.prevVideoHeight=i.data.video_source_height})}initialize(){this.emitter.on("configureView",()=>this.resetPlayheadPosition()),["pause","buffering","seeking","error","pulse"].forEach(e=>this.handleEvent(e)),["playing","pulse"].forEach(e=>this.setPlayheadPosition(e))}},Co=class{constructor(e,t){this.videoDragged=!1,this.seekerElapsedTime=-1,this.dragger=e,this.emitter=t,this.initialize()}initialize(){this.emitter.on("seeking",e=>{this.handleSeeking(e)}),this.emitter.on("seeked",()=>{this.handleSeeked()}),this.emitter.on("viewCompleted",()=>{this.handleViewCompleted()})}handleSeeking(e){Object.assign(this.dragger.data,e),this.videoDragged&&e.viewer_timestamp-this.seekerElapsedTime<=2e3?this.seekerElapsedTime=e.viewer_timestamp:(this.videoDragged&&this.seeker(),this.videoDragged=!0,this.seekerElapsedTime=e.viewer_timestamp,mt(this.dragger.data,"view_seek_count",1),this.dragger.filterData("seeking"))}handleSeeked(){this.seeker()}handleViewCompleted(){this.videoDragged&&(this.seeker(),this.dragger.filterData("seeked")),this.videoDragged=!1,this.seekerElapsedTime=-1}seeker(){let e=me.now(),t=(this.dragger.data.viewer_timestamp??e)-(this.seekerElapsedTime??e);mt(this.dragger.data,"view_seek_duration",t),this.dragger.data.view_max_seek_time=Math.max(this.dragger.data.view_max_seek_time??0,t),this.videoDragged=!1,this.seekerElapsedTime=-1}},wo=class{constructor(e,t){this.launcher=e,this.emitter=t,this.initEventListeners()}initEventListeners(){this.emitter.on("playing",e=>{this.launcher.data.view_time_to_first_frame===void 0&&this.handleTimeFrame(e)}),this.emitter.on("configureView",()=>{this.launcher.data.view_time_to_first_frame=void 0})}handleTimeFrame(e){if(this.launcher.trackTimer.captureViewingProgress(this.launcher,e),this.launcher.data.view_watch_time>0)this.launcher.data.view_time_to_first_frame=this.launcher.data.view_watch_time;else if(this.launcher.data.view_start){let t=e.viewer_timestamp-this.launcher.data.view_start;this.launcher.data.view_time_to_first_frame=t,this.launcher.data.view_watch_time=t}}},ko=class{constructor(e,t){this.lastTrackedWallClockTime=null,this.clock=e,this.emitter=t,this.initialize()}initialize(){this.emitter.on("pulseStart",e=>this.captureViewingProgress(this.clock,e)),this.emitter.on("pulseEnd",e=>this.demolishViewingProgress(this.clock,e))}captureViewingProgress(e,t){let i=t?.viewer_timestamp;if(this.lastTrackedWallClockTime===null)this.lastTrackedWallClockTime=i;else if(i){let r=i-this.lastTrackedWallClockTime;e.data.view_watch_time=(e.data.view_watch_time??0)+r,this.lastTrackedWallClockTime=i}}demolishViewingProgress(e,t){this.captureViewingProgress(e,t),this.lastTrackedWallClockTime=null}},Hr=["viewBegin","ended","loadstart","pause","play","playing","waiting","buffering","buffered","seeked","error","pulse","requestCompleted","requestFailed","requestCanceled"];function ne(e,t,i){let r=new Qs,a=this;a.NavigationStart=ct.getNavigationStartTime(),a.fp=e,a.id=t,i={debug:i?.debug??!1,beaconDomain:i.configDomain??"anlytix.io",disableCookies:i.disableCookies??!1,respectDoNotTrack:i.respectDoNotTrack??!1,allowRebufferTracking:!1,disablePlayheadRebufferTracking:i.disablePlayheadRebufferTracking??!1,errorConverter:function(n){return n},actionableData:i},a.userConfigData=i,a.fetchPlayheadTime=i.actionableData.fetchPlayheadTime,a.fetchStateData=i.actionableData.fetchStateData??function(){return{}},a.allowRebufferTracking=i.allowRebufferTracking,a.disablePlayheadRebufferTracking=i.disablePlayheadRebufferTracking,a.errorConverter=i.errorConverter,a.eventsDispatcher=new ho(e,i.actionableData.data.workspace_id,i),a.data={player_instance_id:Se(),beacon_domain:i.beaconCollectionDomain??i.beaconDomain},a.data.view_sequence_number=1,a.data.player_sequence_number=1,a.lastCheckedEventTime=void 0,a.throbTimeoutId=void 0,a.dispatch=function(n,d){let f=Date.now();if(a.lastCheckedEventTime&&f-a.lastCheckedEventTime>36e5){i?.debug;let C={viewer_timestamp:a.fp.utilityMethods.now()};Object.assign(a.data,C),r.emit("configureView",C),a.lastCheckedEventTime=f}if(n==="play"&&a.data.view_start===void 0){let C={view_start:a.fp.utilityMethods.now()};Object.assign(a.data,C),r.emit("viewBegin",C),a.lastCheckedEventTime=f}Hr.includes(n)&&this.appendVideoState();let p={viewer_timestamp:a.fp.utilityMethods.now(),...d};n!=="videoChange"&&n!=="programChange"&&Object.assign(a.data,p),r.emit(n,p),a.lastCheckedEventTime=f},a.playerDestroyed=void 0,a.initiatePulse=void 0;let s=function(){a.demolishView()};typeof window<"u"&&typeof window.addEventListener<"u"&&(window.addEventListener("pagehide",function(n){n.persisted||s()},!1),window.addEventListener("beforeunload",function(){s()})),r.on("destroy",function(){s()});function o(n){a.dispatch("viewCompleted"),a.filterData("viewCompleted"),a.dispatch("configureView",n),Object.assign(a.data,n)}r.on("videoChange",function(n){o(n)}),r.on("programChange",function(n){let d={...n};o(d),a.dispatch("play"),a.dispatch("playing")}),r.on("configureView",function(){a.refreshViewData(),a.refreshVideoData(),a.appendVideoState(),Object.assign(a.data,i.actionableData.data),a.initializeView()}),a.warning=new ao(a,r),a.gripper=new Co(a,r),a.throughput=new go(a,r),a.playheadHandler=new yo(a,r),a.handlePulse=new bo(a,r),a.handleScaling=new vo(a,r),a.trackTimer=new ko(a,r),a.playbackManager=new fo(a,r),a.eventWaiting=new ro(a,r),a.loaderProps=new io(a,r),a.metricCommencement=new wo(a,r);function l(n){n.resolutionState||(n.resolutionState={prev_source_width:n.data.video_source_width??0,video_source_resolution_dropped_count:0},n.data.video_source_resolution_dropped_count=0)}function u(n){n.resolutionState.prev_source_width>n.data.video_source_width?(n.resolutionState.prev_source_width=n.data.video_source_width,n.data.video_source_resolution_dropped_count++):n.resolutionState.prev_source_width=n.data.video_source_width}r.on("variantChanged",function(){a.data.video_source_width&&(l(a),u(a)),a.appendVideoState(),a.validateData(),a.filterData("variantChanged")}),r.on("playerReady",function(){let n=a.fp.utilityMethods.now();if(a.data.player_init_time){let d=n-a.data.player_init_time;a.data.player_startup_time=d>0?d:0}if(a.NavigationStart&&(a.data.player_init_time??ct.getDomContentLoadedEnd())){let d=Math.min(a.data.player_init_time??1/0,ct.getDomContentLoadedEnd()??1/0)-a.NavigationStart;a.data.page_load_time=d>0?d:0}a.appendVideoState(),a.validateData(),a.filterData("playerReady")}),Hr.forEach(function(n){r.on(n,function(){a.appendVideoState(),a.validateData(),a.filterData(n)})}),a.dispatch("configureView")}ne.prototype.demolishView=function(){this.playerDestroyed||(this.playerDestroyed=!0,this.data.view_start!==void 0&&(this.dispatch("viewCompleted"),this.filterData("viewCompleted"),this.eventsDispatcher.destroy()))};ne.prototype.initializeView=function(){let e=this;this.data.view_id=Se(),mt(e.data,"player_view_count",1)};ne.prototype.appendVideoState=function(){Object.assign(this.data,this.fetchStateData()),this.playheadHandler.handleCurrentPosition(this),this.validateData()};ne.prototype.validateData=function(){let e=["player_width","player_height","video_source_width","video_source_height","video_source_bitrate"],t=["player_source_url","video_source_url"];e.forEach(i=>this.data[i]=parseInt(this.data[i],10)??void 0),t.forEach(i=>{let r=(this.data[i]??"").toLowerCase();(r.startsWith("data:")||r.startsWith("blob:"))&&(this.data[i]="MSE style URL")})};ne.prototype.filterData=function(e){if(this.data.view_id){this.data.player_source_duration>0||this.data.video_source_duration>0?this.data.video_source_is_live=!1:this.data.video_source_duration===void 0&&(this.data.video_source_is_live=!0);let t=this.data.video_source_url??this.data.player_source_url;t&&(this.data.video_source_domain=Wt(t),this.data.video_source_hostname=re(t));let i={...this.data};this.eventsDispatcher.sendData(e,i),this.data.view_sequence_number++,this.data.player_sequence_number++,this.handlePulseEvent(this),e==="viewCompleted"&&delete this.data.view_id}};ne.prototype.handlePulseEvent=e=>{e.throbTimeoutId&&clearTimeout(e.throbTimeoutId),e.warning.hasErrorOccurred||(e.throbTimeoutId=setTimeout(()=>{e.data.player_is_paused||e.dispatch("pulse")},1e4))};ne.prototype.refreshViewData=function(){let e=this;Object.keys(this.data).forEach(function(t){t.indexOf("view_")===0&&delete e.data[t]}),this.data.view_sequence_number=1};ne.prototype.refreshVideoData=function(){let e=this;Object.keys(this.data).forEach(function(t){t.indexOf("video_")===0&&delete e.data[t]})};var So=(e,t,i,r,a)=>{let s=S=>Ys(S),o=(S,w,g,k,y={})=>{let h=s(w);return{request_event_type:S,request_bytes_loaded:h.bytesLoaded,request_start:h.requestStart,request_response_start:h.responseStart,request_response_end:h.responseEnd,request_type:"manifest",request_hostname:re(g),request_url:g??"",request_response_headers:k,...y}},l=(S,w)=>{let g=w.levels.map(h=>({width:h.width,height:h.height,bitrate:h.bitrate,attrs:h.attrs})),k=w.audioTracks.map(h=>({name:h.name,language:h.lang,bitrate:h.bitrate})),y=o(S,w.stats,w.url,dt(w.networkDetails),{request_rendition_lists:{media:g,audio:k,video:{}}});a("requestCompleted",y)},u=(S,w)=>{let g=w.details,k=o(S,w.stats,g.url,dt(w.networkDetails),{video_source_is_live:g.live});a("requestCompleted",k)},n=(S,w)=>{let g=o(S,w.stats,w.details.url,dt(w.networkDetails));a("requestCompleted",g)},d=(S,w)=>{let g=w.frag,k=o(S,w.stats??g.stats,w.networkDetails?.responseURL,dt(w.networkDetails),{request_type:g.type==="main"?"media":g.type,request_video_width:e.levels[g.level]?.width,request_video_height:e.levels[g.level]?.height});a("requestCompleted",k)},f=(S,w)=>{let g=e.levels[w.level];if(!g?.attrs?.BANDWIDTH)return;let k={video_source_fps:parseFloat(g.attrs["FRAME-RATE"])||void 0,video_source_bitrate:g.attrs.BANDWIDTH,video_source_width:g.width,video_source_height:g.height,video_source_rendition_name:g.name,video_source_codec:g.videoCodec};a("variantChanged",k)},p=(S,w)=>{let g=w.frag?._url||"";a("requestCanceled",{request_event_type:S,request_url:g,request_type:"media",request_hostname:re(g)})},C=(S,w)=>{let{type:g,details:k,frag:y,url:h,response:v,fatal:T,reason:E,level:B,error:O,event:N,err:q}=w,A=y?.url??h??"",z=[A?`url: ${A}`:"",v?.code||v?.text?`response: ${v.code}, ${v.text}`:"",E?`failure reason: ${E}`:"",B?`level: ${B}`:"",O?`error: ${O}`:"",N?`event: ${N}`:"",q?.message?`error message: ${q.message}`:""].filter(Boolean).join(`
`);if(T&&r?.automaticErrorTracking){a("error",{player_error_code:g,player_error_message:k,player_error_context:z});return}if(new Set([t.ErrorDetails.MANIFEST_LOAD_ERROR,t.ErrorDetails.MANIFEST_LOAD_TIMEOUT,t.ErrorDetails.FRAG_LOAD_ERROR,t.ErrorDetails.FRAG_LOAD_TIMEOUT,t.ErrorDetails.LEVEL_LOAD_ERROR,t.ErrorDetails.LEVEL_LOAD_TIMEOUT,t.ErrorDetails.AUDIO_TRACK_LOAD_ERROR,t.ErrorDetails.AUDIO_TRACK_LOAD_TIMEOUT,t.ErrorDetails.SUBTITLE_LOAD_ERROR,t.ErrorDetails.SUBTITLE_LOAD_TIMEOUT,t.ErrorDetails.KEY_LOAD_ERROR,t.ErrorDetails.KEY_LOAD_TIMEOUT]).has(k)){let W={[t.ErrorDetails.FRAG_LOAD_ERROR]:"media",[t.ErrorDetails.FRAG_LOAD_TIMEOUT]:"media",[t.ErrorDetails.AUDIO_TRACK_LOAD_ERROR]:"audio",[t.ErrorDetails.AUDIO_TRACK_LOAD_TIMEOUT]:"audio",[t.ErrorDetails.SUBTITLE_LOAD_ERROR]:"subtitle",[t.ErrorDetails.SUBTITLE_LOAD_TIMEOUT]:"subtitle",[t.ErrorDetails.KEY_LOAD_ERROR]:"encryption",[t.ErrorDetails.KEY_LOAD_TIMEOUT]:"encryption"}[g]??"manifest";a("requestFailed",{request_error:k,request_url:A,request_hostname:re(A),request_type:W,request_error_code:v?.code,request_error_text:v?.text})}};i?.fp&&(i.fp.destroyHlsMonitoring=()=>{e.off(t.Events.MANIFEST_LOADED,l),e.off(t.Events.LEVEL_LOADED,u),e.off(t.Events.AUDIO_TRACK_LOADED,n),e.off(t.Events.FRAG_LOADED,d),e.off(t.Events.LEVEL_SWITCHED,f),e.off(t.Events.FRAG_LOAD_EMERGENCY_ABORTED,p),e.off(t.Events.ERROR,C),e.off(t.Events.DESTROYING,i.fp?.destroyHlsMonitoring),delete i.fp?.destroyHlsMonitoring}),e.on(t.Events.MANIFEST_LOADED,l),e.on(t.Events.LEVEL_LOADED,u),e.on(t.Events.AUDIO_TRACK_LOADED,n),e.on(t.Events.FRAG_LOADED,d),e.on(t.Events.LEVEL_SWITCHED,f),e.on(t.Events.FRAG_LOAD_EMERGENCY_ABORTED,p),e.on(t.Events.ERROR,C),e.on(t.Events.DESTROYING,i.fp?.destroyHlsMonitoring)},Eo=(e,t,i,r)=>{let a=["x-cdn","content-type","content-length","last-modified","server","x-request-id","cf-ray","x-amz-cf-id","x-akamai-request-id"];function s(y=""){let h={};return y.trim().split(/[\r\n]+/).forEach(v=>{if(!v)return;let[T,...E]=v.split(": ");if(!T)return;let B=T.toLowerCase(),O=E.join(": ");(a.includes(B)||B.startsWith("x-litix-"))&&(h[T]=O)}),h}let o=(y,h)=>{if(!(y?.endDate??y?.requestEndDate))return{};let{url:v,bytesLoaded:T,requestStartDate:E,requestEndDate:B,startDate:O,firstByteDate:N,endDate:q,mediaType:A}=y,z=re(v),W=new Date(O??E).getTime(),c=new Date(N).getTime(),b=new Date(q??B).getTime(),_=typeof h.getMetricsFor=="function"?h.getMetricsFor(A).HttpList:h.getDashMetrics().getHttpRequests(A),I=_?.[_.length-1],F=I?s(I._responseHeaders??""):void 0;return{requestStart:W,requestResponseStart:c,requestResponseEnd:b,requestBytesLoaded:T,requestResponseHeaders:F,requestHostname:z,requestUrl:v}},l=(y,h,v)=>{r("requestCompleted",{request_event_type:y,request_start:h.requestStart,request_response_start:h.requestResponseStart,request_response_end:h.requestResponseEnd,request_bytes_loaded:h.requestBytesLoaded??-1,request_type:v,request_response_headers:h.requestResponseHeaders,request_hostname:h.requestHostname,request_url:h.requestUrl})},u=()=>{let y=e.getDashMetrics().getHttpRequests("Manifest"),h=y[y.length-1];if(h?._responseHeaders)return h._responseHeaders.split(`
`).find(v=>v.toLowerCase().startsWith("content-type:"))?.split(":")[1]?.trim()},n=y=>{let{type:h,data:v}=y,T=v?.url,E=u(),B={requestStart:0,requestResponseStart:0,requestResponseEnd:0,requestBytesLoaded:-1,requestResponseHeaders:void 0,requestHostname:re(T),requestUrl:T,request_mime_type:E};l(h,B,"manifest")},d=y=>{let h=o(y.request,e),v=`${y.chunk?.mediaInfo?.type}_init`;l(y.type,h,v)},f=y=>{let{type:h,request:v,chunk:T}=y,E=T?.mediaInfo?.type,B=o(v,e);l(h,B,E)},p={},C=y=>{let h=/.*codecs\*?="(.*)"/.exec(y);return h?h[1]:void 0},S=()=>{let{video:y,audio:h,totalBitrate:v}=p;if(y&&typeof y.bitrate=="number"){if(!y.width||!y.height)return;let T=y.bitrate;if(h&&typeof h.bitrate=="number"&&(T+=h.bitrate),T!==v)return p.totalBitrate=T,{video_source_bitrate:T,video_source_height:y.height,video_source_width:y.width,video_source_codec:C(y.codec??"")}}},w=y=>{let{mediaType:h,newRepresentation:v,newQuality:T}=y;if(h==="video"&&typeof v=="object"){t?.fp?.dispatch("variantChanged",{video_source_bitrate:v.bandwidth,video_source_height:v.height,video_source_width:v.width,video_source_codec:v.codecs});return}if(typeof T=="number"&&(h==="video"||h==="audio")){let E=e.getBitrateInfoListFor(h).find(O=>O.qualityIndex===T);if(!E||typeof E.bitrate!="number")return;p[h]={...E,codec:e.getCurrentTrackFor(h)?.codec};let B=S();B&&t?.fp?.dispatch("variantChanged",B)}},g=y=>{let h=y.request,v=y.mediaType,T=h?.action,E=h?.url,B=E?re(E):"";t?.fp?.dispatch("requestCanceled",{request_event_type:T,request_url:E,request_type:v,request_hostname:B})},k=function(y){let h="",{error:v}=y;if(!v)return;let{data:T}=v,E=T?.request??{},B=T?.response??{};if(v.code===27&&t?.fp.dispatch("requestFailed",{request_error:`${E.type}_${E.action}`,request_url:E.url,request_hostname:E.url?re(E.url):"",request_type:E.mediaType,request_error_code:B.status,request_error_text:B.statusText}),E.url&&(h+="url: "+E.url+`
`),B.status||B.statusText){let O=B.status??"",N=B.statusText??"";h+="response: "+O+", "+N+`
`}i?.automaticErrorTracking&&t?.fp.dispatch("error",{player_error_code:v.code,player_error_message:v.message,player_error_context:h})};t?.fp&&(t.fp.destroyDashMonitoring=()=>{e.off("error",k),e.off("fragmentLoadingAbandoned",g),e.off("qualityChangeRendered",w),e.off("manifestLoaded",n),e.off("initFragmentLoaded",d),e.off("mediaFragmentLoaded",f),delete t.fp?.destroyDashMonitoring}),e.on("error",k),e.on("fragmentLoadingAbandoned",g),e.on("qualityChangeRendered",w),e.on("manifestLoaded",n),e.on("initFragmentLoaded",d),e.on("mediaFragmentLoaded",f)},pt={},To=["loadstart","pause","play","playing","seeking","seeked","timeupdate","waiting","error","ended"],Bo={1:"MEDIA_ERR_ABORTED",2:"MEDIA_ERR_NETWORK",3:"MEDIA_ERR_DECODE",4:"MEDIA_ERR_SRC_NOT_SUPPORTED"},zr={tracker:function(e,t){let i=Zs(e),r=i[0],a=i[2],s=t.hlsjs,o=t.dashPlayer,l=t.Hls??window.Hls,u=t.dashjs??window.dashjs,n="unknown";l?n="hls":u&&(n="dash");let d=this;if(!r||a!=="video"&&a!=="audio")return;r?.fp&&r.fp.destroy();let f=i[1],p={automaticErrorTracking:t.automaticErrorTracking??!0},C={hls:{name:"hls.js Player",version:l?.version??"",sdk:"fastpix-hls-monitoring"},dash:{name:"dash.js Player",version:u?.Version??"",sdk:"fastpix-dash-monitoring"},unknown:{name:"",version:"",sdk:"fastpix-data-monitoring"}}[n];t={...t,...p},t.data={player_software_name:C.name,player_software_version:C.version,player_fastpix_sdk_name:C.sdk,player_fastpix_sdk_version:"1.0.5",...t.data};let S=function(g){return["auto","metadata"].includes(g)};t.fetchPlayheadTime=function(){return Math.floor(1e3*r.currentTime)},t.fetchStateData=function(){let g,k,y=s?.url,h=o&&typeof o.getSource=="function"&&o.getSource();return{player_is_paused:r.paused,player_width:r.offsetWidth,player_height:r.offsetHeight,player_autoplay_on:r.autoplay,player_preload_on:S(r.preload),player_is_fullscreen:document&&!!(document.fullscreenElement??document?.webkitFullscreenElement??document?.mozFullScreenElement??document?.msFullscreenElement),video_source_height:r.videoHeight,video_source_width:r.videoWidth,video_source_url:y??h??r.currentSrc,video_source_domain:Wt(y??h??r.currentSrc),video_source_hostname:re(y??h??r.currentSrc),video_source_duration:Math.floor(1e3*r.duration),video_poster_url:r.poster,player_language_code:r.lang,view_dropped_frame_count:(g=r)===null||g===void 0||(k=g.getVideoPlaybackQuality)===null||k===void 0?void 0:k.call(g).droppedVideoFrames}},r.fp=r.fp??{},r.fp.dispatch=function(g,k){d.dispatch(f,g,k)},r.fp.listeners={},r.fp.deleted=!1,r.fp.destroy=function(){Object.keys(r.fp.listeners).forEach(function(g){r.removeEventListener(g,r.fp.listeners[g],!1)}),delete r.fp.listeners,n==="hls"&&r.fp?.destroyHlsMonitoring?r.fp?.destroyHlsMonitoring():n==="dash"&&r.fp?.destroyDashMonitoring&&r.fp?.destroyDashMonitoring(),r.fp.deleted=!0,r.fp.dispatch("destroy"),delete r?.fp},d.configure(f,t),d.dispatch(f,"playerReady"),r.paused||(d.dispatch(f,"play"),r.readyState>2&&d.dispatch(f,"playing")),To.forEach(function(g){g==="error"&&!t.automaticErrorTracking||(r.fp.listeners[g]=function(){let k={};if(g==="error"){if(!r.error||r.error?.code===1)return;k.player_error_code=r.error?.code,k.player_error_message=Bo[r.error?.code]??r.error?.message}d.dispatch(f,g,k)},r.addEventListener(g,r.fp.listeners[g],!1))});let w=(g,k)=>r.fp.dispatch(g,k);s&&So(s,l,r,p,w),o?.on&&Eo(o,r,p,w)},utilityMethods:to,configure:function(e,t){if(Or()&&t?.respectDoNotTrack&&t?.debug,e){let i=Pr(e);i&&(pt[i]=new ne(this,i,t))}},dispatch:function(e,t,i){if(e&&t){let r=Pr(e);r&&pt[r]&&(pt[r].dispatch(t,i),t==="destroy"&&delete pt[r])}}},Ur=zr;typeof window<"u"&&(window.fastpixMetrix=zr);var Lo="1.0.17",_o=e=>{let t={"metadata-workspace-key":"workspace_id","metadata-video-title":"video_title","metadata-viewer-user-id":"viewer_id","metadata-video-id":"video_id","metadata-experiment-name":"experiment_name","metadata-player-name":"player_name","metadata-player-version":"player_version","metadata-video-duration":"video_duration","metadata-view-session-id":"view_session_id","metadata-page-context":"page_context","metadata-sub-property-id":"sub_property_id","metadata-video-content-type":"video_content_type","metadata-player-poster":"player_poster","metadata-video-drm-type":"video_drm_type","metadata-video-encoding-variant":"video_encoding_variant","metadata-video-language-code":"video_language_code","metadata-video-producer":"video_producer","metadata-video-variant-name":"video_variant_name","metadata-video-cdn":"video_cdn","metadata-cdn":"cdn","metadata-beacon-domain":"beacon_domain","metadata-video-variant-id":"video_variant_id","metadata-video-series":"video_series","metadata-video-poster-url":"video_poster_url","metadata-player-softer-name":"player_software_name","metadata-player-software-version":"player_software_version","metadata-custom-1":"custom_1","metadata-custom-2":"custom_2","metadata-custom-3":"custom_3","metadata-custom-4":"custom_4","metadata-custom-5":"custom_5","metadata-custom-6":"custom_6","metadata-custom-7":"custom_7","metadata-custom-8":"custom_8","metadata-custom-9":"custom_9","metadata-custom-10":"custom_10","metadata-browser-name":"browser_name","metadata-os-name":"os_name","metadata-os-version":"os_version","metadata-player-init-time":"player_init_time"},i={};return Object.entries(t).forEach(([r,a])=>{let s=e.getAttribute(r);s!==null&&(i[a]=s)}),e.streamType&&(i.video_stream_type=e.streamType),i};function Wr(e,t,i,r){let a=_o(e);a={...a,player_software_name:"fastpix-player-data-monitoring",player_software_version:Lo};let s=e.hasAttribute("enable-debug"),o=e.hasAttribute("disable-cookies"),l=e.hasAttribute("respect-do-not-track"),u=e.hasAttribute("disable-data-monitoring"),n=e.getAttribute("metadata-workspace-key"),d=!u&&!!n,f=e.getAttribute("config-domain")||"anlytix.io";d&&Ur.tracker(t,{debug:s,hlsjs:i,Hls:r,disableCookies:o,data:a,respectDoNotTrack:l,configDomain:f})}var jr=`<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 33 33" fill="none">
<g clip-path="url(#clip0_13552_13979)">
<path d="M27.3965 12.1973V9.57227C27.3965 8.87607 27.1199 8.20839 26.6276 7.71611C26.1354 7.22383 25.4677 6.94727 24.7715 6.94727V6.07227C24.7715 5.37607 24.4949 4.70839 24.0026 4.21611C23.5104 3.72383 22.8427 3.44727 22.1465 3.44727H11.6465C10.9503 3.44727 10.2826 3.72383 9.79033 4.21611C9.29805 4.70839 9.02148 5.37607 9.02148 6.07227V6.94727C8.32529 6.94727 7.65761 7.22383 7.16533 7.71611C6.67305 8.20839 6.39648 8.87607 6.39648 9.57227V12.1973C5.70029 12.1973 5.03261 12.4738 4.54033 12.9661C4.04805 13.4584 3.77148 14.1261 3.77148 14.8223V27.0723C3.77148 27.7685 4.04805 28.4361 4.54033 28.9284C5.03261 29.4207 5.70029 29.6973 6.39648 29.6973H27.3965C28.0927 29.6973 28.7604 29.4207 29.2526 28.9284C29.7449 28.4361 30.0215 27.7685 30.0215 27.0723V14.8223C30.0215 14.1261 29.7449 13.4584 29.2526 12.9661C28.7604 12.4738 28.0927 12.1973 27.3965 12.1973ZM10.7715 6.07227C10.7715 5.8402 10.8637 5.61764 11.0278 5.45355C11.1919 5.28945 11.4144 5.19727 11.6465 5.19727H22.1465C22.3785 5.19727 22.6011 5.28945 22.7652 5.45355C22.9293 5.61764 23.0215 5.8402 23.0215 6.07227V6.94727H10.7715V6.07227ZM8.14648 9.57227C8.14648 9.3402 8.23867 9.11764 8.40277 8.95355C8.56686 8.78945 8.78942 8.69727 9.02148 8.69727H24.7715C25.0035 8.69727 25.2261 8.78945 25.3902 8.95355C25.5543 9.11764 25.6465 9.3402 25.6465 9.57227V12.1973H8.14648V9.57227ZM28.2715 27.0723C28.2715 27.3043 28.1793 27.5269 28.0152 27.691C27.8511 27.8551 27.6285 27.9473 27.3965 27.9473H6.39648C6.16442 27.9473 5.94186 27.8551 5.77777 27.691C5.61367 27.5269 5.52148 27.3043 5.52148 27.0723V14.8223C5.52148 14.5902 5.61367 14.3676 5.77777 14.2035C5.94186 14.0395 6.16442 13.9473 6.39648 13.9473H27.3965C27.6285 13.9473 27.8511 14.0395 28.0152 14.2035C28.1793 14.3676 28.2715 14.5902 28.2715 14.8223V27.0723Z" fill="currentColor"/>
<path d="M21.2715 19.3723L16.2402 16.2223C15.958 16.0478 15.6344 15.9519 15.3027 15.9443C14.971 15.9368 14.6433 16.0179 14.3534 16.1793C14.0636 16.3408 13.8221 16.5766 13.6538 16.8626C13.4856 17.1486 13.3968 17.4743 13.3965 17.8061V24.0886C13.3954 24.4203 13.4833 24.7463 13.6511 25.0326C13.8188 25.3188 14.0603 25.5549 14.3502 25.7161C14.6406 25.878 14.9689 25.9593 15.3012 25.9516C15.6335 25.9439 15.9577 25.8475 16.2402 25.6723L21.2715 22.5223C21.5392 22.3558 21.76 22.1237 21.9131 21.8482C22.0662 21.5726 22.1465 21.2625 22.1465 20.9473C22.1465 20.6321 22.0662 20.322 21.9131 20.0464C21.76 19.7709 21.5392 19.5388 21.2715 19.3723ZM20.344 21.0436L15.3127 24.1848C15.2958 24.1937 15.2768 24.1981 15.2576 24.1977C15.2385 24.1972 15.2197 24.192 15.2032 24.1824C15.1866 24.1728 15.1727 24.1591 15.1628 24.1427C15.1529 24.1263 15.1473 24.1077 15.1465 24.0886V17.8061C15.1444 17.7866 15.1483 17.7669 15.1577 17.7497C15.1671 17.7325 15.1815 17.7186 15.199 17.7098C15.2189 17.7031 15.2404 17.7031 15.2602 17.7098H15.3127L20.344 20.8511C20.3603 20.8613 20.3737 20.8755 20.383 20.8923C20.3922 20.9092 20.3971 20.9281 20.3971 20.9473C20.3971 20.9665 20.3922 20.9854 20.383 21.0023C20.3737 21.0191 20.3603 21.0333 20.344 21.0436Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_13552_13979">
<rect width="28" height="28" fill="currentColor" transform="translate(2.89648 2.57227)"/>
</clipPath>
</defs>
</svg>`;var $r=`<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 33 33" fill="none">
<path d="M24.2648 27.3409C23.9891 27.3409 23.7247 27.2314 23.5298 27.0365C23.3348 26.8415 23.2253 26.5771 23.2253 26.3014V6.84517C23.2253 6.56948 23.3348 6.30508 23.5298 6.11013C23.7247 5.91518 23.9891 5.80566 24.2648 5.80566H25.8394C26.1151 5.80566 26.3795 5.91518 26.5744 6.11013C26.7694 6.30508 26.8789 6.56948 26.8789 6.84517V26.3014C26.8789 26.5771 26.7694 26.8415 26.5744 27.0365C26.3795 27.2314 26.1151 27.3409 25.8394 27.3409H24.2648ZM5.04944 7.19029V25.8945C5.04944 26.7383 6.00215 27.2305 6.69057 26.7422L20.2364 17.1335C20.8297 16.7125 20.8186 15.8281 20.2143 15.4227L6.66848 6.32698C5.97798 5.86362 5.04944 6.35816 5.04944 7.19029Z" fill="currentColor"/>
</svg>`;var Zr=`<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 33 33" fill="none">
<path d="M7.90513 27.3409C8.18082 27.3409 8.44522 27.2314 8.64017 27.0365C8.83512 26.8415 8.94464 26.5771 8.94464 26.3014V6.84517C8.94464 6.56948 8.83512 6.30508 8.64017 6.11013C8.44522 5.91518 8.18082 5.80566 7.90513 5.80566H6.33053C6.05483 5.80566 5.79043 5.91518 5.59548 6.11013C5.40054 6.30508 5.29102 6.56948 5.29102 6.84517V26.3014C5.29102 26.5771 5.40054 26.8415 5.59548 27.0365C5.79043 27.2314 6.05483 27.3409 6.33053 27.3409H7.90513ZM27.1205 7.19029V25.8945C27.1205 26.7383 26.1678 27.2305 25.4794 26.7422L11.9335 17.1335C11.3402 16.7125 11.3514 15.8281 11.9556 15.4227L25.5014 6.32698C26.1919 5.86362 27.1205 6.35816 27.1205 7.19029Z" fill="currentColor"/>`;function Kr(e){e.prevButton=e.prevButton||document.createElement("button"),e.prevButton.innerHTML=Zr,e.prevButton.className=e.prevButton.className||"playlistPrevButton playlistButtonHidden",e.nextButton=e.nextButton||document.createElement("button"),e.nextButton.innerHTML=$r,e.nextButton.className=e.nextButton.className||"playlistNextButton playlistButtonHidden",e.leftControls.contains(e.prevButton)||e.leftControls.appendChild(e.prevButton),e.leftControls.contains(e.nextButton)||e.leftControls.appendChild(e.nextButton),e.updatePlaylistControlsVisibility=()=>{let t=Array.isArray(e.playlist)&&e.playlist.length>0,i=getComputedStyle(e.leftControls).getPropertyValue("--previous-episode-button").trim(),r=getComputedStyle(e.leftControls).getPropertyValue("--next-episode-button").trim(),a=i==="none",s=r==="none";t?(e.controlsContainer.classList.add("hasPlaylist"),e.prevButton.classList.remove("playlistButtonHidden"),e.prevButton.classList.add("playlistButtonVisible"),e.nextButton.classList.remove("playlistButtonHidden"),e.nextButton.classList.add("playlistButtonVisible")):(e.controlsContainer.classList.remove("hasPlaylist"),e.prevButton.classList.remove("playlistButtonVisible"),e.prevButton.classList.add("playlistButtonHidden"),e.nextButton.classList.remove("playlistButtonVisible"),e.nextButton.classList.add("playlistButtonHidden")),e.prevButton.classList.toggle("playlistPrevButtonDisabledByCSS",a),e.playPauseButton.classList.toggle("playlistPrevButtonDisabledByCSS",a),e.forwardRewindControlsWrapper.classList.toggle("playlistPrevButtonDisabledByCSS",a),e.forwardRewindControlsWrapper.classList.toggle("playlistNextButtonDisabledByCSS",s),e.nextButton.classList.toggle("playlistNextButtonDisabledByCSS",s)},e.updatePlaylistControlsVisibility()}function Ao(){if(typeof document>"u"||document.getElementById("fastpix-ce-slot-fouc"))return;let e=document.createElement("style");e.id="fastpix-ce-slot-fouc",e.textContent="fastpix-player:not(:defined) > * { display: none !important; }",document.head.appendChild(e)}Ao();function Po(e){return e.map(t=>{let i=t.playbackId??t["playback-id"];return i?{playbackId:i,token:t.token??t.token,drmToken:t.drmToken??t["drm-token"],customDomain:t.customDomain??t["custom-domain"],skipIntroStart:t.skipIntroStart??t["skip-intro-start"],skipIntroEnd:t.skipIntroEnd??t["skip-intro-end"],nextEpisodeOverlay:t.nextEpisodeOverlay??t["next-episode-button-overlay"],title:t.title,thumbnail:t.thumbnail,duration:t.duration}:null}).filter(Boolean)}function Mo(e){let t=e.defaultPlaybackId??null;if(!t)return 0;let i=e.playlist.findIndex(r=>r.playbackId===t);return i>=0?i:0}function Do(e){if(!e.playPauseButton)return;let t=e.hasAttribute("auto-play")||e.hasAttribute("loop-next"),i=getComputedStyle(e).getPropertyValue("--initial-play-button").trim();Array.isArray(e.playlist)&&e.playlist.length>0?t||i==="none"?e.playPauseButton.style.setProperty("display","none"):e.playPauseButton.style.setProperty("display","flex"):e.playPauseButton.style.setProperty("display","var(--initial-play-button, flex)")}function Io(e){let t=e.playlist[e.currentIndex];if(!t?.playbackId)return;(e.hasAttribute("auto-play")||e.hasAttribute("loop-next"))&&R(e),e.loadByPlaybackId(t.playbackId,{token:t.token,drmToken:t.drmToken,customDomain:t.customDomain}),!e.hideDefaultPlaylistPanel&&typeof $=="function"&&e.playlistPanel&&$(e)}var jt=class extends Oe.HTMLElement{constructor(){super();this.playlist=[];this.currentIndex=0;this.audioTracks=[];this.subtitleTracks=[];this.currentAudioTrackId=null;this.currentSubtitleTrackId=null;this.hideDefaultPlaylistPanel=!1;this.externalPlaylistOpen=!1;this.isCartOpen=!1;this.isSidebarHovered=!1;this._initShoppableRequested=!1;this._reenterPiPOnReady=!1;this.isHotspotVisible=!1;this.cartData={productSidebarConfig:{},products:[]};this.hotspotPauseTimeout=null;this.hasAutoClosedSidebar=!1;this._lastActiveProductEl=null;this.openCartSidebar=()=>{try{this.playbackRateDiv&&this.playbackRateDiv.style?.display!=="none"&&(this.playbackRateDiv.style.display="none"),this.resolutionMenu&&this.resolutionMenu.style?.display!=="none"&&(this.resolutionMenu.style.display="none"),this.subtitleMenu&&this.subtitleMenu.style?.display!=="none"&&(this.subtitleMenu.style.display="none"),this.audioMenu&&this.audioMenu.style?.display!=="none"&&(this.audioMenu.style.display="none")}catch{}if(!this.cartSidebar)return;this.cartSidebar.style.display="flex";let i=this.cartSidebar.offsetWidth;this.cartSidebar.style.width="var(--shoppable-sidebar-width)",this.isCartOpen=!0,this.cartButton.innerHTML='<svg width="24" height="24" viewBox="0 0 24 24"><path d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12l-4.89 4.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/></svg>',this.progressBar.classList.add("cartSidebarOpen-progress-bar"),this.bottomRightDiv.classList.add("cartSidebarOpen-bottom-right-div"),this.dispatchEvent(new CustomEvent("productBarMax",{detail:{opened:!0}})),K(this)};this.closeCartSidebar=()=>{let i=this.isCartOpen;this.cartSidebar&&(this.cartSidebar.style.width="0",this.cartSidebar.style.display="none",this.isCartOpen=!1,this.cartButton.innerHTML='<svg width="24" height="24" viewBox="0 0 24 24"><path d="M7 18c-1.104 0-2 .896-2 2s.896 2 2 2 2-.896 2-2-.896-2-2-2zm10 0c-1.104 0-2 .896-2 2s.896 2 2 2 2-.896 2-2-.896-2-2-2zM7.334 16h9.332c.822 0 1.542-.502 1.847-1.264l3.479-8.12A1 1 0 0 0 21 5H5.21l-.94-2.342A1 1 0 0 0 3.333 2H1a1 1 0 1 0 0 2h1.333l3.6 8.982-1.35 2.44C3.52 16.14 4.477 18 6 18h12a1 1 0 1 0 0-2H7.334z"/></svg>',this.progressBar.classList.remove("cartSidebarOpen-progress-bar"),this.bottomRightDiv.classList.remove("cartSidebarOpen-bottom-right-div"),this.bottomRightDiv&&(this.bottomRightDiv.style.right=""),this.dispatchEvent(new CustomEvent("productBarMin",{detail:{opened:!1}})),K(this),i&&this.triggerCartIconDance())};this.showCartButton=()=>{this.cartButton&&(this.cartButton.style.display="flex",this.cartButton.style.visibility="visible",this.cartButton.style.opacity="1")};this.ensureShoppableShortsCartButton=()=>{(this.getAttribute?this.getAttribute("theme"):null)==="shoppable-shorts"&&this.cartButton&&(this.cartButton.style.display="flex",this.cartButton.style.visibility="visible",this.cartButton.style.opacity="1",this.cartButton.style.position="absolute",this.cartButton.style.top="16px",this.cartButton.style.right="16px",this.cartButton.style.zIndex="1600")};this.triggerCartIconDance=()=>{let i=this.cartButton;if(i)try{i.classList.remove("cart-dance"),i.offsetWidth,i.classList.add("cart-dance"),window.setTimeout(()=>i.classList.remove("cart-dance"),600)}catch{}};this._readyState=0,this.config=Et,this.hls=null,this.video=m.createElement("video"),this.resolutionFlagPause=!1,this.isLoading=!1,this.isOnline=navigator.onLine,this.userSelectedLevel=null,this.isBufferFlushed=!1,this.isBuffering=!1,this.pauseAfterLoading=!1,this.resolutionSwitching=!1,this.disabledAllCaptions=!1,this.wasManuallyPaused=!1,this.video.controls=!1,this.progressBarVisible=!1,this.isError=!1,this.cache=new Map,this.initialPlayClick=!1,this.defaultPlaybackRate="1",this.lastClickedPlaybackRateButton=null,this.playbackRates=[],this.isInitialLoad=!0,this.videoEnded=!1,this.pausedOnCasting=!1,this.currentCastSession=null,this.castMediaDuration=null,this.currentSubtitleTrackIndex=-1,this.chapters=[],this._src=null,this.isMuted=!1,this.previousChapter=null,this.retryButtonVisible=!1,ui(this),this.showPostPlayOverlay=!!(this.cartData.productSidebarConfig?.showPostPlayOverlay??!1),this.wrapper=m.createElement("div"),this.wrapper.style.position="relative",this.controlsContainer=m.createElement("div"),this.controlsContainer.className="controlsContainer",this.leftControls=m.createElement("div"),this.leftControls.className="leftControls",this.mobileControls=m.createElement("div"),this.mobileControls.className="mobileControls",this.mobileControlButtonsBlock=m.createElement("div"),this.mobileControlButtonsBlock.className="mobileControlsButtonsBlock",this.timeDisplay=m.createElement("div"),this.timeDisplay.className="timeDisplay",this.subtitleMenu=m.createElement("div"),this.subtitleMenu.style.display="none",this.ccButton=m.createElement("button"),this.ccButton.className="ccButton",this.ccButton.innerHTML=Cr,this.castButton=m.createElement("button"),this.castButton.className="castButton",this.castButton.innerHTML=Fe,this.castButton.style.setProperty("--cast-button-display","none"),pr(this),this.retryButton=m.createElement("button"),this.retryButton.innerHTML=`<svg width="25%" height="25%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 6V2L7 7L12 12V8C15.31 8 18 10.69 18 14C18 17.31 15.31 20 12 20C8.69 20 6 17.31 6 14H4C4 18.42 7.58 22 12 22C16.42 22 20 18.42 20 14C20 9.58 16.42 6 12 6Z" fill="currentColor"/>
        </svg>`,this.retryButton.className="retryButton",this.retryButton.style.position="absolute",this.retryButton.style.top="50%",this.retryButton.style.left="50%",this.retryButton.style.transform="translate(-50%, -50%)",this.retryButton.style.display="none",this.retryButton.style.background="transparent",this.forwardRewindControlsWrapper=m.createElement("div"),this.forwardRewindControlsWrapper.className="forwardRewindControlsWrapper",this.progressBarContainer=m.createElement("div"),this.progressBarContainer.className="progressBarContainer",this.controlsContainer.appendChild(this.progressBarContainer),this.skipIntroButton=m.createElement("button"),this.skipIntroButton.className="skipIntroButton",this.skipIntroButton.textContent="Skip intro",this.skipIntroButton.style.display="none",this.controlsContainer.appendChild(this.skipIntroButton),this.nextEpisodeButton=m.createElement("button"),this.nextEpisodeButton.className="nextEpisodeButton",this.nextEpisodeButton.textContent="Next episode",this.nextEpisodeButton.style.display="none",this.controlsContainer.appendChild(this.nextEpisodeButton),this.thumbnail=m.createElement("div"),this.thumbnail.className="thumbnailSeeking",this.thumbnailSeekingContainer=m.createElement("div"),this.thumbnailSeekingContainer.className="thumbnailSeekingContainer",this.chapterDisplay=m.createElement("div"),this.chapterDisplay.className="thumbnailChapterDisplay",this.progressBar=m.createElement("input"),this.progressBar.className="progressBar",this.progressBar.type="range",this.progressBar.min="0",this.progressBar.value="0",this.progressBar.step="0.01",this.progressBarContainer.appendChild(this.progressBar),this.playPauseButton=m.createElement("button"),this.playPauseButton.style.zIndex="1500",this.playPauseButton.style.position="absolute",this.playPauseButton.classList.add("initialPlayBigButton"),this.playPauseButton.classList.add("initialplayPauseButtonStyle"),this.bottomRightDiv=m.createElement("div"),this.bottomRightDiv.className="bottomRightContainer",this.resolutionMenuButton=m.createElement("button"),this.resolutionMenuButton.innerHTML=wr,this.resolutionMenuButton.className="resolutionMenuButton",this.resolutionMenuButton.style.zIndex="2px",this.bottomRightDiv.appendChild(this.resolutionMenuButton),this.resolutionMenu=m.createElement("div"),this.resolutionMenu.classList.add("resolution-menu"),this.resolutionMenu.style.display="none",this.bottomRightDiv.appendChild(this.resolutionMenu),this.video.textTracks.addEventListener("addtrack",r=>{let a=r.track;(a.kind==="subtitles"||a.kind==="captions")&&(a.mode="hidden",a.addEventListener("cuechange",()=>{if(this.hasAttribute("hide-native-subtitles"))this.subtitleContainer.innerHTML="",this.subtitleContainer.classList.remove("contained");else if(a.activeCues&&a.activeCues.length>0){let o=a.activeCues[0];if(o&&this.initialPlayClick){let l=o.text??"";this.subtitleContainer.innerHTML=l,this.subtitleContainer.classList.add("contained")}else this.subtitleContainer.innerHTML="",this.subtitleContainer.classList.remove("contained")}else this.subtitleContainer.innerHTML="",this.subtitleContainer.classList.remove("contained");try{let o=a.activeCues&&a.activeCues.length>0?a.activeCues[0].text??"":"",l=a.activeCues?a.activeCues[0]:null;this.dispatchEvent(new CustomEvent("fastpixsubtitlecue",{detail:{text:o,language:a.language||void 0,startTime:typeof l?.startTime=="number"?l.startTime:void 0,endTime:typeof l?.endTime=="number"?l.endTime:void 0}}))}catch{}}))}),this.wasPausedBeforeSwitch=!1,this.audioMenuButton=m.createElement("button"),this.audioMenuButton.innerHTML=kr,this.audioMenuButton.className="audioMenuButton",this.audioMenuButton.id="audioMenuButton",this.audioMenuButton.style.zIndex="2px",this.audioMenu=m.createElement("div"),this.audioMenu.style.display="none",this.audioMenu.classList.add("audio-menu"),this.bottomRightDiv.appendChild(this.audioMenuButton),this.audioMenuButton.appendChild(this.audioMenu),cr(this),this.subtitleContainer=m.createElement("div"),this.subtitleContainer.id="subtitleContainer",this.subtitleContainer.classList.add("subtitle-container"),this.videoOverLay=m.createElement("div"),this.videoOverLay.className="video-overlay",this.wrapper.appendChild(this.video),this.wrapper.appendChild(this.videoOverLay),this.userSlotsOverlay=m.createElement("div"),this.userSlotsOverlay.className="fastpix-user-slots",this.userSlotsOverlay.setAttribute("part","user-slots"),this.userSlotsOverlay.setAttribute("aria-hidden","true");let i=["top-left","top-center","top-right","center-left","center-right","bottom-left","bottom-center","bottom-right"];for(let r of i){let a=m.createElement("div");a.className=`fastpix-slot-region fastpix-slot-${r}`,a.setAttribute("data-slot",r);let s=m.createElement("slot");s.name=r,a.appendChild(s),this.userSlotsOverlay.appendChild(a)}this.wrapper.appendChild(this.userSlotsOverlay),this.pipButton=m.createElement("button"),this.pipButton.className="pipButton",this.pipButton.innerHTML=Sr,this.fullScreenButton=m.createElement("button"),this.fullScreenButton.className="fullScreenButton",this.fullScreenButton.innerHTML=Le,this.fastForwardButton=m.createElement("button"),this.fastForwardButton.innerHTML=Er,this.fastForwardButton.id="increaseTimeBtn",this.fastForwardButton.className="increaseTimeBtn",this.rewindBackButton=m.createElement("button"),this.rewindBackButton.innerHTML=Tr,this.rewindBackButton.id="decreaseTimeBtn",this.rewindBackButton.className="decreaseTimeBtn",mr(this),this.playPauseButton.innerHTML=te,this.parentVolumeDiv=m.createElement("div"),this.parentVolumeDiv.className="parentVolumeDiv",this.parentVolumeDiv.style.zIndex="1",this.volumeButton=m.createElement("button"),this.volumeButton.className="volumeButton",this.volumeButton.innerHTML=ee,this.volumeButton.style.display="none",this.volumeiOSButton=m.createElement("button"),this.volumeiOSButton.className="volumeiOSButton",fr(this),this.volumeControl=m.createElement("input"),this.volumeControl.className="volumeControl",this.volumeControl.type="range",this.volumeControl.min="0",this.volumeControl.max="1",this.volumeControl.step="0.2",this.volumeControl.value="1",this.volumeControl.style.display="none",this.volumeControl.style.borderRadius="0.313rem",yr(this),document.addEventListener("fullscreenchange",()=>{let r=!!document.fullscreenElement;this.fullScreenButton.innerHTML=r?ni:Le}),this.loader=m.createElement("div"),this.loader.className="spinner",this.loader.style.position="absolute",this.loader.style.bottom="50%",this.loader.style.left="50%",this.loader.style.marginLeft="-20px",this.loader.style.marginTop="-20px",this.loader.style.display="none",this.bottomCenterDiv=m.createElement("div"),this.bottomCenterDiv.className="bottomCenterDiv",this.spacer=m.createElement("div"),this.spacer.className="spacer",this.wrapper.className="parent",this.controlsContainer.appendChild(this.leftControls),this.controlsContainer.appendChild(this.bottomCenterDiv),this.controlsContainer.appendChild(this.playPauseButton),this.controlsContainer.appendChild(this.bottomRightDiv),this.wrapper.appendChild(this.loader),this.wrapper.appendChild(this.controlsContainer),this.wrapper.appendChild(this.subtitleContainer),this.cartButton=m.createElement("button"),this.cartButton.className="cartButton",this.cartButton.innerHTML='<svg width="24" height="24" viewBox="0 0 24 24"><path d="M7 18c-1.104 0-2 .896-2 2s.896 2 2 2 2-.896 2-2-.896-2-2-2zm10 0c-1.104 0-2 .896-2 2s.896 2 2 2 2-.896 2-2-.896-2-2-2zM7.334 16h9.332c.822 0 1.542-.502 1.847-1.264l3.479-8.12A1 1 0 0 0 21 5H5.21l-.94-2.342A1 1 0 0 0 3.333 2H1a1 1 0 1 0 0 2h1.333l3.6 8.982-1.35 2.44C3.52 16.14 4.477 18 6 18h12a1 1 0 1 0 0-2H7.334z"/></svg>',this.cartButton.style.position="absolute",this.cartButton.style.top="16px",this.cartButton.style.right="16px",this.cartButton.style.zIndex="1600",this.cartButton.style.background="#fff",this.cartButton.style.borderRadius="50%",this.cartButton.style.boxShadow="0 2px 8px rgba(0,0,0,0.10)",this.cartButton.style.width="40px",this.cartButton.style.height="40px",this.cartButton.style.display="flex",this.cartButton.style.alignItems="center",this.cartButton.style.justifyContent="center",this.cartButton.style.border="none",this.cartButton.style.cursor="pointer",this.cartButton.style.opacity="0.6",this.cartGotoLink=this.getAttribute("product-link")||void 0,this.cartButton.onclick=r=>{if(r.stopPropagation(),this.getAttribute("theme")==="shoppable-shorts"){let a=this.cartGotoLink||"https://www.fastpix.io";window.open(a,"_blank","noopener,noreferrer");return}this.getAttribute("theme")==="shoppable-video-player"&&(this.isCartOpen?this.closeCartSidebar():this.openCartSidebar())}}get readyState(){return this._readyState}get currentTime(){return this.video?this.video.currentTime:0}get buffered(){return this.video?this.video.buffered:0}get duration(){return this.video?this.video.duration:0}get paused(){return this.video?this.video.paused:!0}get ended(){return this.video?this.video.ended:!1}get volume(){return this.video?this.video.volume:1}get muted(){return this.video?this.video.muted:!1}set muted(i){if(!this.video)return;let r=!!i;this.video.muted=r,r?(this.setAttribute("muted",""),this.mutedAttribute=!0):(this.removeAttribute("muted"),this.mutedAttribute=!1),V()&&Z(this.video.volume,r)}get seeking(){return this.video?this.video.seeking:!1}get src(){return this._src}set src(i){this._src=i,this.video&&(this.video.src=i||"")}get currentSrc(){return this.src??"Default src"}get networkState(){return this.video?this.video.networkState:0}get error(){return this.video?this.video.error:null}get videoWidth(){let i=this.video.offsetWidth;return this.video?i:0}get videoHeight(){let i=this.video.offsetHeight;return this.video?i:0}get playbackRate(){return this.video?this.video.playbackRate:1}get controls(){return this.video?this.video.controls:!1}get poster(){return this.video?this.video.poster:""}get autoplay(){let i=this.hasAttribute("auto-play");return this.video?i:!1}set autoplay(i){let r=!!i;r?(this.setAttribute("auto-play",""),this.hasAutoPlayAttribute=!0):(this.removeAttribute("auto-play"),this.hasAutoPlayAttribute=!1),this.video&&(this.video.autoplay=r)}get loop(){let i=this.hasAttribute("loop");return this.video?i:!1}set loop(i){let r=!!i;r?(this.setAttribute("loop",""),this.loopAttribute=!0,this.loopEnabled=!0):(this.removeAttribute("loop"),this.loopAttribute=!1,this.loopEnabled=!1),this.video&&(this.video.loop=r)}play(){return this.video?.play?.()??Promise.reject(new Error("Video not ready"))}pause(){this.video?.pause?.()}mute(){this.video&&(this.setAttribute("muted",""),this.mutedAttribute=!0,this.video.muted=!0,V()&&Z(this.video.volume,!0))}unmute(){this.video&&(this.removeAttribute("muted"),this.mutedAttribute=!1,this.video.muted=!1,this.video.volume=1,V()&&Z(1,!1))}enableAutoplay(){this.setAttribute("auto-play",""),this.hasAutoPlayAttribute=!0,this.video&&(this.video.autoplay=!0)}disableAutoplay(){this.removeAttribute("auto-play"),this.hasAutoPlayAttribute=!1,this.video&&(this.video.autoplay=!1)}enableLoop(){this.video&&(this.setAttribute("loop",""),this.loopAttribute=!0,this.loopEnabled=!0,this.video.loop=!0)}disableLoop(){this.video&&(this.removeAttribute("loop"),this.loopAttribute=!1,this.loopEnabled=!1,this.video.loop=!1)}addChapters(i){i.sort((r,a)=>r.startTime-a.startTime),i.forEach((r,a)=>{r.endTime??(r.endTime=a<i.length-1?i[a+1].startTime:this.video.duration)}),this.chapters=i,j(this)}addShoppableData(i){if(!i||typeof i!="object")return;let r={...this.cartData?.productSidebarConfig??{},...i.productSidebarConfig??{}},a=Array.isArray(i.products)?i.products:this.cartData?.products??[];this.cartData={productSidebarConfig:r,products:a},this.showPostPlayOverlay=!!this.cartData.productSidebarConfig?.showPostPlayOverlay;let s=this.getAttribute?this.getAttribute("theme"):null;(s==="shoppable-video-player"||s==="shoppable-shorts")&&!this._initShoppableRequested&&lt(this),this.cartSidebar&&nt(this),this.dispatchEvent(new CustomEvent("shoppabledatachange",{detail:this.cartData}))}activeChapter(){let i=this.video.currentTime,r=this.chapters.find(s=>i>=s.startTime&&i<(s.endTime??1/0)),a=r?{startTime:r.startTime,endTime:r.endTime,value:r.value}:null;return(!this.previousChapter&&a||this.previousChapter&&a&&(this.previousChapter.startTime!==a.startTime||this.previousChapter.endTime!==a.endTime||this.previousChapter.value!==a.value))&&(this.previousChapter=a,this.dispatchEvent(new Event("chapterchange"))),a}convertChaptersToPlayerFormat(i){function r(a){let[s,o,l]=a.split(":").map(Number);return s*3600+o*60+l}return i.chapters.map(a=>{let s=r(a.startTime),o=a.endTime?r(a.endTime):void 0;return{startTime:s,endTime:o,value:a.title,summary:a.summary}})}convertOpenAIChapters(i){return i.map(r=>{let a=r.start.split(":");return{startTime:parseInt(a[0])*3600+parseInt(a[1])*60+parseInt(a[2]),value:r.title}})}addPlaylist(i){if(Array.isArray(i)){this.playlist=Po(i),this.currentIndex=Mo(this);try{let r=this.playlist[this.currentIndex]??{},a=r?.skipIntroStart!=null?parseFloat(r.skipIntroStart):NaN,s=r?.skipIntroEnd!=null?parseFloat(r.skipIntroEnd):NaN,o=r?.nextEpisodeOverlay!=null?parseFloat(r.nextEpisodeOverlay):NaN;this.removeAttribute("skip-intro-start"),this.removeAttribute("skip-intro-end"),this.removeAttribute("next-episode-button-overlay"),Number.isFinite(a)?(this.setAttribute("skip-intro-start",String(a)),this.skipIntroStart=a):this.skipIntroStart=null,Number.isFinite(s)?(this.setAttribute("skip-intro-end",String(s)),this.skipIntroEnd=s):this.skipIntroEnd=null,Number.isFinite(o)?(this.setAttribute("next-episode-button-overlay",String(o)),this.nextEpisodeOverlayStart=o):this.nextEpisodeOverlayStart=null}catch{}typeof this.updatePlaylistControlsVisibility=="function"&&this.updatePlaylistControlsVisibility(),Do(this),Io(this)}}next(){if(this.currentIndex<this.playlist.length-1){this.currentIndex++;let i=this.playlist[this.currentIndex];if(i?.playbackId){this.destroy();try{document.pictureInPictureElement&&(this._reenterPiPOnReady=!0,document.exitPictureInPicture?.())}catch{}this.controlsContainer.style.setProperty("--controls","none"),R(this);try{let r=i?.skipIntroStart!=null?parseFloat(i.skipIntroStart):NaN,a=i?.skipIntroEnd!=null?parseFloat(i.skipIntroEnd):NaN,s=i?.nextEpisodeOverlay!=null?parseFloat(i.nextEpisodeOverlay):NaN;this.removeAttribute("skip-intro-start"),this.removeAttribute("skip-intro-end"),this.removeAttribute("next-episode-button-overlay"),Number.isFinite(r)?(this.setAttribute("skip-intro-start",String(r)),this.skipIntroStart=r):this.skipIntroStart=null,Number.isFinite(a)?(this.setAttribute("skip-intro-end",String(a)),this.skipIntroEnd=a):this.skipIntroEnd=null,Number.isFinite(s)?(this.setAttribute("next-episode-button-overlay",String(s)),this.nextEpisodeOverlayStart=s):this.nextEpisodeOverlayStart=null}catch{}this.loadByPlaybackId(i.playbackId,{token:i.token,drmToken:i.drmToken,customDomain:i.customDomain}),!this.hideDefaultPlaylistPanel&&typeof $=="function"&&this.playlistPanel&&$(this)}}this.hasAutoClosedSidebar=!1}previous(){if(this.currentIndex>0){this.currentIndex--;let i=this.playlist[this.currentIndex];if(i?.playbackId){this.destroy();try{document.pictureInPictureElement&&(this._reenterPiPOnReady=!0,document.exitPictureInPicture?.())}catch{}this.controlsContainer&&this.controlsContainer.style.setProperty("--controls","none"),R(this);try{let r=i?.skipIntroStart!=null?parseFloat(i.skipIntroStart):NaN,a=i?.skipIntroEnd!=null?parseFloat(i.skipIntroEnd):NaN,s=i?.nextEpisodeOverlay!=null?parseFloat(i.nextEpisodeOverlay):NaN;this.removeAttribute("skip-intro-start"),this.removeAttribute("skip-intro-end"),this.removeAttribute("next-episode-button-overlay"),Number.isFinite(r)?(this.setAttribute("skip-intro-start",String(r)),this.skipIntroStart=r):this.skipIntroStart=null,Number.isFinite(a)?(this.setAttribute("skip-intro-end",String(a)),this.skipIntroEnd=a):this.skipIntroEnd=null,Number.isFinite(s)?(this.setAttribute("next-episode-button-overlay",String(s)),this.nextEpisodeOverlayStart=s):this.nextEpisodeOverlayStart=null}catch{}this.loadByPlaybackId(i.playbackId,{token:i.token,drmToken:i.drmToken,customDomain:i.customDomain}),!this.hideDefaultPlaylistPanel&&typeof $=="function"&&this.playlistPanel&&$(this)}}this.hasAutoClosedSidebar=!1}async loadByPlaybackId(i,r){try{this.subtitleContainer&&(this.subtitleContainer.innerHTML="",this.subtitleContainer.classList.remove("contained")),Array.from(this.video?.textTracks??[]).forEach(u=>u.mode="disabled"),this.subtitleMenu&&(this.subtitleMenu.style.display="none"),this.currentSubtitleTrackIndex=-1}catch{}this.playbackId=i,r?.token&&(this.token=r.token),r?.drmToken&&(this.drmToken=r.drmToken),r?.customDomain&&this.setAttribute("custom-domain",r.customDomain);let a=r?.customDomain||this.getAttribute("custom-domain"),s=null;(this.streamType==="on-demand"||this.streamType==="live-stream")&&(s=a?`https://stream.${a}`:"https://stream.fastpix.io"),r?.drmToken&&wt(this),await Ne(this,i,r?.token??this.token??null,s??void 0,this.streamType??null),this._src=qe(),this.video.src=this._src??"",this.video.load(),this.video.addEventListener("canplay",()=>{Y(this)&&P(this),this.controlsContainer&&this.controlsContainer.style.setProperty("--controls","flex");try{this._reenterPiPOnReady&&!document.pictureInPictureElement&&this.video?.requestPictureInPicture?.().catch(()=>{})}finally{this._reenterPiPOnReady=!1}this.suppressErrorUntilReady=!1,x(this,this.playbackId??"",this.thumbnailUrlFinal??"",this.streamType??""),!this.hideDefaultPlaylistPanel&&typeof $=="function"&&this.playlistPanel&&$(this)},{once:!0}),r?.emitPlaybackChange&&this.dispatchEvent(new CustomEvent("playbackidchange",{detail:{playbackId:i,isFromPlaylist:this.playlist.length>0,currentIndex:this.currentIndex,totalItems:this.playlist.length,status:"ready"}}))}selectEpisodeByPlaybackId(i){let r=this.playlist.findIndex(a=>a.playbackId===i);if(r!==-1){this.currentIndex=r;let a=this.playlist[this.currentIndex];try{let s=a?.skipIntroStart!=null?parseFloat(a.skipIntroStart):NaN,o=a?.skipIntroEnd!=null?parseFloat(a.skipIntroEnd):NaN,l=a?.nextEpisodeOverlay!=null?parseFloat(a.nextEpisodeOverlay):NaN;this.removeAttribute("skip-intro-start"),this.removeAttribute("skip-intro-end"),this.removeAttribute("next-episode-button-overlay"),Number.isFinite(s)?(this.setAttribute("skip-intro-start",String(s)),this.skipIntroStart=s):this.skipIntroStart=null,Number.isFinite(o)?(this.setAttribute("skip-intro-end",String(o)),this.skipIntroEnd=o):this.skipIntroEnd=null,Number.isFinite(l)?(this.setAttribute("next-episode-button-overlay",String(l)),this.nextEpisodeOverlayStart=l):this.nextEpisodeOverlayStart=null}catch{}this.loadByPlaybackId(i,{token:a.token,drmToken:a.drmToken,customDomain:a.customDomain,emitPlaybackChange:!0}),!this.hideDefaultPlaylistPanel&&typeof $=="function"&&this.playlistPanel&&$(this),this.hideDefaultPlaylistPanel&&this.externalPlaylistOpen&&(this.externalPlaylistOpen=!1,(this.playlistSlot?Array.from(this.playlistSlot.children):[]).forEach(o=>o.style.pointerEvents="none"),this.dispatchEvent(new CustomEvent("playlisttoggle",{detail:{open:!1,hasPlaylist:Array.isArray(this.playlist)&&this.playlist.length>0,currentIndex:this.currentIndex,totalItems:Array.isArray(this.playlist)?this.playlist.length:0,playbackId:this.playbackId??null},bubbles:!0,composed:!0})))}this.hasAutoClosedSidebar=!1}handleVideoEvent(i){this.dispatchEvent(new CustomEvent(i.type,{detail:i,bubbles:!0,composed:!0}))}onFragmentParsed(i){i.frag&&this.debugAttribute}updateEpisodeControls(){if(this.episodeType==="episodic"&&this.episodes&&this.currentEpisodeIndex!==void 0){this.episodeControlsContainer.style.display="inline-flex",this.episodeControlsContainer.style.alignItems="center",this.episodeControlsContainer.style.gap="8px",this.episodeControlsContainer.style.marginLeft="12px";let i=this.currentEpisodeIndex===0,r=this.currentEpisodeIndex===this.episodes.length-1;this.prevEpisodeButton.disabled=i,this.nextEpisodeButton.disabled=r,this.prevEpisodeButton.style.opacity=i?"0.5":"1",this.nextEpisodeButton.style.opacity=r?"0.5":"1"}else this.episodeControlsContainer.style.display="none"}destroy(){try{at(this);try{M(this)}catch{}this.hotspotPauseTimeout&&(clearTimeout(this.hotspotPauseTimeout),this.hotspotPauseTimeout=null);try{document.pictureInPictureElement&&(this._reenterPiPOnReady=!0,document.exitPictureInPicture?.())}catch{}try{this.video?.pause?.()}catch{}try{this.hls?.destroy?.(),this.video.fp&&this.video.fp.destroy();let i=ae();this.config={...this.config,startFragPrefetch:Ue(this.streamType)},this.hls=new i(this.config),We(this)}catch{}}catch{}}seekForward(i){ue(this,i)}seekBackward(i){ue(this,-i)}async connectedCallback(){let i=this.getAttribute("custom-domain");ai(this),await Xi(this),(this.hasAttribute("auto-play")||this.hasAttribute("autoplay-shorts")||this.hasAttribute("loop-next"))&&this.controlsContainer?.style.setProperty("--initial-play-button","none"),this.customStyle=m.createElement("style"),this.customStyle.innerHTML=Br,ge(this),hr(this),Xt(this,async()=>{if(!this.playbackId||Array.isArray(this.playlist)&&this.playlist.length>0){this.suppressErrorUntilReady=!0;return}(this.hasAttribute("auto-play")||this.hasAttribute("autoplay-shorts")||this.hasAttribute("loop-next"))&&R(this);let n=null;(this.streamType==="on-demand"||this.streamType==="live-stream")&&(n=i?`https://stream.${i}`:"https://stream.fastpix.io");let d=!1;this.drmToken&&(d=!0),d&&wt(this),await Ne(this,this.playbackId??null,this.token??null,n??void 0,this.streamType??null),this._src=qe(),/^((?!chrome|android).)*safari/i.test(navigator.userAgent)||(Ei(),this.castButton?.innerHTML?.trim()&&Ti(this.castButton,this.video,this._src??"",this))}),ei.forEach(n=>{this.video.addEventListener(n,this.handleVideoEvent.bind(this))}),ur(this),ge(this),vr(this),Ji(this),Lr(this),this.playPauseButton.addEventListener("click",()=>{this.videoEnded=!1,M(this),x(this,this.playbackId??"",this.thumbnailUrlFinal??"",this.streamType??"")}),this.nextEpisodeButton&&this.nextEpisodeButton.addEventListener("click",()=>{try{if(typeof this.customNext=="function"){this.customNext.call(this,this);return}else this.next()}catch{}this.next()}),this.wrapper.addEventListener("click",n=>{(n.target===this.playPauseButton||this.playPauseButton.contains(n.target))&&(n.stopImmediatePropagation(),this.videoEnded=!1,M(this),x(this,this.playbackId??"",this.thumbnailUrlFinal??"",this.streamType??""))},!0);let a="100%",s="100%";if(this.loadStartTime=performance.now(),this.hasAttribute("autoplay-shorts")&&(this.video.load(),this.video.addEventListener("canplay",()=>{x(this,this.playbackId??"",this.thumbnailUrlFinal??"",this.streamType??"")},{once:!0})),this.playbackRatesAttribute!==null){let n=this.playbackRatesAttribute.split(" ").map(f=>parseFloat(f)),d=[...new Set(n)];this.playbackRates.splice(0,this.playbackRates.length,...d)}else this.playbackRates=[1,1.2,1.5,1.7,2];this.playbackRateDiv=m.createElement("div"),this.playbackRateDiv.className="playbackRate-menu",this.playbackRateDiv.style.display="none";let o=Ar(this.defaultPlaybackRateAttribute);if(o&&(this.defaultPlaybackRate=o),this.playbackRates.forEach(n=>{let d=m.createElement("button");d.style.padding="5px 6px",d.textContent=`${n}x`,d.title=`${n}x`,d.className="playbackRateButton",String(n)===String(this.defaultPlaybackRate)&&(d.classList.add("active"),this.lastClickedPlaybackRateButton=d),d.addEventListener("click",()=>{try{this.playbackRateDiv?.querySelectorAll(".playbackRateButton.active")?.forEach(p=>p.classList.remove("active"))}catch{}_r(this,n,d)}),this.playbackRateDiv?.appendChild(d)}),this.playbackRateButton=m.createElement("button"),this.playbackRateButton.textContent=`${this.defaultPlaybackRate}x`,this.playbackRateButton.className="playbackRateButtonInitial",this.playlistButton=m.createElement("button"),this.playlistButton.innerHTML=jr,this.playlistButton.className="playlistButton",this.hideDefaultPlaylistPanel)this.playlistSlot=document.createElement("div"),this.playlistSlot.className="playlist-slot",this.playlistSlot.style.position="absolute",this.playlistSlot.style.top="0",this.playlistSlot.style.left="0",this.playlistSlot.style.right="0",this.playlistSlot.style.bottom="0",this.playlistSlot.style.opacity="0",this.playlistSlot.style.transition="opacity 0.9s ease",this.playlistSlot.style.pointerEvents="none",this.playlistSlot.style.zIndex="9999",this.controlsContainer.appendChild(this.playlistSlot),Array.from(this.children).filter(d=>{let f=d.getAttribute("slot"),p=d.getAttribute("data-fastpix-slot");return f==="playlist-panel"||p==="playlist-panel"}).forEach(d=>this.playlistSlot?.appendChild(d));else{this.playlistPanel=document.createElement("div"),this.playlistPanel.className="playlist-panel",this.playlistPanel.style.maxHeight="400px";let n=document.createElement("div");n.className="playlist-header",n.textContent="Episode List",this.playlistItems=document.createElement("div"),this.playlistItems.className="playlist-items-wrapper",this.playlistPanel.appendChild(n),this.bottomRightDiv.appendChild(this.playlistPanel)}br(this),this.bottomRightDiv.appendChild(this.playlistButton),this.bottomRightDiv.appendChild(this.ccButton),this.bottomRightDiv.appendChild(this.playbackRateButton),this.castButton?.innerHTML?.trim()&&this.bottomRightDiv.appendChild(this.castButton),this.bottomRightDiv.appendChild(this.pipButton),this.bottomRightDiv.appendChild(this.fullScreenButton),this.bottomRightDiv.appendChild(this.subtitleMenu),this.bottomRightDiv.appendChild(this.playbackRateDiv),gi(this);try{this.addEventListener("playlisttoggle",n=>{let d=!!n?.detail?.open;if(!this.hideDefaultPlaylistPanel)return;try{M(this)}catch{}this.externalPlaylistOpen=d,(this.playlistSlot?Array.from(this.playlistSlot.children):[]).forEach(p=>p.style.pointerEvents=d?"auto":"none"),this.playlistSlot&&this.playlistSlot.style&&(this.playlistSlot.style.opacity=d?"1":"0",this.playlistSlot.style.transition="opacity 0.9s ease")})}catch{}let l=parseFloat(this.startTimeAttribute)||0;this.video.currentTime=l,this.wrapper.style.width=a,this.wrapper.style.height=s,this.wrapper.style.position="relative",this.video.style.display="flex",this.video.style.alignItems="center",this.video.style.justifyContent="center",Ri(this),this.parentLiveTitleContainer=m.createElement("div"),this.parentLiveTitleContainer.className="parentTextContainer",this.titleElement=m.createElement("div"),this.liveStreamDisplay=m.createElement("button"),Fi(this),this.controlsContainer.appendChild(this.parentLiveTitleContainer),Wr(this,this.video,this.hls,ae()),this.video.loop=!!this.loopAttribute,this.bufferedRange=m.createElement("div"),this.bufferedRange.style.position="absolute",this.bufferedRange.style.top="0",this.bufferedRange.style.left="0",this.bufferedRange.style.height="100%",this.bufferedRange.style.width="0",this.progressBar.appendChild(this.bufferedRange),this.parentVolumeDiv.appendChild(this.volumeButton),this.parentVolumeDiv.appendChild(this.volumeControl),gr(this),xi(this),Kr(this),yi(this),bi(this);try{this.addEventListener("playbackidchange",()=>{typeof this.updatePlaylistControlsVisibility=="function"&&this.updatePlaylistControlsVisibility()}),this.mutationObserver||(this.mutationObserver=new MutationObserver(n=>{for(let d of n)d.type==="attributes"&&d.attributeName==="style"&&typeof this.updatePlaylistControlsVisibility=="function"&&this.updatePlaylistControlsVisibility()}),this.mutationObserver.observe(this,{attributes:!0,attributeFilter:["style"]}))}catch{}let u=this.getAttribute?this.getAttribute("theme"):null;(u==="shoppable-video-player"||u==="shoppable-shorts")&&(lt(this),u==="shoppable-shorts"&&setTimeout(()=>{requestAnimationFrame(()=>this.ensureShoppableShortsCartButton())},100))}disconnectedCallback(){at(this),this.hls?.destroy(),this.video.fp&&this.video.fp.destroy()}static get observedAttributes(){return["theme"]}attributeChangedCallback(i,r,a){i==="theme"&&a&&(a==="shoppable-video-player"||a==="shoppable-shorts")&&(this._initShoppableRequested=!1,lt(this))}positionHotspot(i,r,a,s){let o=s??this.wrapper,l=o?.clientWidth||o?.offsetWidth||0,u=o?.clientHeight||o?.offsetHeight||0,n=32,d=32,f=Math.min(Math.max(Number(r)||0,0),100),p=Math.min(Math.max(Number(a)||0,0),100);if(!l||!u){i.style.left=`${f}%`,i.style.top=`${p}%`;return}let C=f/100*l,S=p/100*u,w=C-n/2,g=S-d/2,k=Math.max(0,Math.min(l-n,Math.round(w))),y=Math.max(0,Math.min(u-d,Math.round(g)));i.style.left=`${k}px`,i.style.top=`${y}px`}removeAllHotspots(){this.wrapper.querySelectorAll(".hotspot").forEach(r=>{r.parentNode&&r.parentNode.removeChild(r)}),this.isHotspotVisible=!1}getPlaylistSlot(){return this.playlistSlot??null}getVideoOverlay(){return this.videoOverLay??null}getUserSlotsOverlay(){return this.userSlotsOverlay??null}setNextHandler(i){typeof i=="function"&&(this.customNext=i)}setPrevHandler(i){typeof i=="function"&&(this.customPrev=i)}getAudioTracks(){let{audioTracks:i,currentAudioTrackId:r}=Ve(this);return this.audioTracks=i,this.currentAudioTrackId=r,this.audioTracks}getSubtitleTracks(){let{subtitleTracks:i,currentSubtitleTrackId:r}=It(this);return this.subtitleTracks=i,this.currentSubtitleTrackId=r,this.subtitleTracks}setAudioTrack(i){let r=this.hls;if(!r||!Array.isArray(r.audioTracks)||typeof i!="string")return;let a=i.trim().toLowerCase();if(!a)return;let s=Array.isArray(r.audioTracks)?r.audioTracks:[],o=Array.isArray(this.audioTracksRetrieved)?this.audioTracksRetrieved:[],l=n=>{if(!Array.isArray(n)||n.length===0)return-1;let d=n.findIndex(f=>(f?.name??"").toString().trim().toLowerCase()===a);return d>=0||(d=n.findIndex(f=>(f?.lang??"").toString().trim().toLowerCase()===a)),d},u=l(s);if(u<0){let n=l(o);n>=0&&n<s.length&&(u=n)}if(!(u<0||u>=s.length||s.length===0))try{this.debugAttribute;let n=typeof r.audioTrack=="number"?r.audioTrack:-1;Rt(this,this.hls,u,n),r.audioTrack=u,Ht(this.hls,n,u)||ye(this,!0),queueMicrotask(()=>{ie(()=>{try{r?.audioTrack!==u&&(r.audioTrack=u),Re(this)}catch{}})});let d=this.getAudioTracks(),f=this.currentAudioTrackId;this.dispatchEvent(new CustomEvent("fastpixaudiochange",{detail:{tracks:d,currentId:f,currentTrack:Array.isArray(d)?d.find(p=>p?.isCurrent)??null:null}}))}catch{}}setSubtitleTrack(i){if(!this.video||!this.video.textTracks)return;let a=Array.from(this.video.textTracks||[]).map((l,u)=>({track:l,index:u})).filter(({track:l})=>l.kind==="subtitles"||l.kind==="captions");if(i===null)a.forEach(({track:l})=>{l.mode="disabled"});else{if(typeof i!="string")return;let l=i.trim().toLowerCase();if(!l)return;let u=a.find(({track:d})=>(d?.label??"").toString().trim().toLowerCase()===l),n=u&&typeof u.index=="number"?u.index:-1;if(n<0)return;a.forEach(({track:d,index:f})=>{d.mode=f===n?"showing":"disabled"})}let s=this.getSubtitleTracks(),o=this.currentSubtitleTrackId;this.dispatchEvent(new CustomEvent("fastpixsubtitlechange",{detail:{tracks:s,currentId:o,currentTrack:Array.isArray(s)?s.find(l=>l?.isCurrent)??null:null}}))}disableSubtitles(){ve(this);let i=this.getSubtitleTracks(),r=this.currentSubtitleTrackId;this.dispatchEvent(new CustomEvent("fastpixsubtitlechange",{detail:{tracks:i,currentId:r}})),this.subtitleMenu&&this.subtitleMenu.style?.display!=="none"&&(this.subtitleMenu.style.display="none")}getQualityLevels(){return Pt(this)}setQualityLevel(i){ji(this,i)}setQualityAuto(){tt(this)}getPlaybackQuality(){return zi(this)}};Oe.customElements.get("fastpix-player")||Oe.customElements.define("fastpix-player",jt);
