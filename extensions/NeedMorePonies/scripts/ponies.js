var canHazPwnies = false;
var hazInitedPwnies = false;

function togglePwnies() {
	if(!hazInitedPwnies) {
		var cfg = {
			"baseurl": "http://panzi.github.com/Browser-Ponies/",
			"fadeDuration": 500,
			"fps": 25,
			"speed": 3,
			"audioEnabled": false,
			"showFps": false,
			"showLoadProgress": true,
			"speakProbability": 0,
			"spawn":
			{
				"applejack":1,
				"fluttershy":1,
				"pinkie pie":1,
				"rainbow dash":1,
				"rarity":1,
				"twilight sparkle":1 // is best pony
			},
			"autostart": false
		};

		BrowserPonies.setBaseUrl(cfg.baseurl);
		BrowserPonies.loadConfig(BrowserPoniesBaseConfig);
		BrowserPonies.loadConfig(cfg);
		hazInitedPwnies = true;
	}

	if(canHazPwnies) {
		BrowserPonies.stop();
		canHazPwnies = false;
	} else {
		BrowserPonies.start();
		canHazPwnies = true;
	}
}

shortcut.add('Ctrl+Alt+P', togglePwnies);
