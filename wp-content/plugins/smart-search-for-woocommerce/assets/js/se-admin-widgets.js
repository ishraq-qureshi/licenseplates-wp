SearchaniseAdmin = {};
SearchaniseAdmin.host = searchanise_options.host;
SearchaniseAdmin.PrivateKey = searchanise_options.parent_private_key;
SearchaniseAdmin.ReSyncLink = searchanise_options.re_sync_link;
SearchaniseAdmin.LastRequest = searchanise_options.last_request;
SearchaniseAdmin.LastResync = searchanise_options.last_resync;
SearchaniseAdmin.ConnectLink = searchanise_options.connect_link;
SearchaniseAdmin.Platform = searchanise_options.platform;
SearchaniseAdmin.AddonStatus = searchanise_options.status;
SearchaniseAdmin.AddonVersion = searchanise_options.version;
SearchaniseAdmin.PlatformEdition = searchanise_options.platform_edition;
SearchaniseAdmin.PlatformVersion = searchanise_options.platform_version;
SearchaniseAdmin.ShowResultsControlPanel = true;
SearchaniseAdmin.Engines = [];

if (searchanise_options.s_engines.length) {
	for (var i = 0; i < searchanise_options.s_engines.length; i++) {
		var engine = searchanise_options.s_engines[i];

		SearchaniseAdmin.Engines.push({
			PrivateKey: engine.private_key,
			LangCode: engine.lang_code,
			Name : engine.language_name,
			ExportStatus: engine.export_status,
			PriceFormat: {
				rate : 1.0,
				symbol: searchanise_options.symbol,
				decimals: searchanise_options.decimals,
				decimals_separator: searchanise_options.decimals_separator,
				thousands_separator: searchanise_options.thousands_separator,
				after: false
			}
		});
	}
}
