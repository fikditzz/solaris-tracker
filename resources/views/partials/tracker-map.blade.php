<div class="tracker-map">
    <div class="map-col dual-only">
        <div class="map-title map-title-azimuth">Azimuth (top view)</div>
        <div class="dial dial-azimuth">
            <span class="dial-mark dial-n">N</span>
            <span class="dial-mark dial-s">S</span>
            <span class="dial-mark dial-w">W</span>
            <span class="dial-mark dial-e">E</span>
            <div class="dial-axis"></div>
            <div class="dial-pointer" id="{{ $prefix }}-azimuth-ptr">
                <div class="panel-cell"><span class="material-symbols-rounded" aria-hidden="true">solar_power</span></div>
            </div>
        </div>
        <div class="map-reading"><span id="{{ $prefix }}-map-az">--</span>°</div>
    </div>
    <div class="map-col">
        <div class="map-title map-title-elevation">Tilt (side view)</div>
        <div class="dial dial-elevation">
            <div class="tilt-ground"></div>
            <div class="tilt-zenith"></div>
            <div class="tilt-post"></div>
            <div class="tilt-anchor">
                <div class="tilt-panel" id="{{ $prefix }}-elevation-ptr"></div>
            </div>
            <div class="tilt-pivot"></div>
        </div>
        <div class="map-reading"><span id="{{ $prefix }}-map-el">--</span>°</div>
    </div>
</div>
