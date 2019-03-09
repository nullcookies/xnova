export default {
	PAGE_LOAD (state, data)
	{
		state.start_time = Math.floor(((new Date()).getTime()) / 1000)

		for (let key in data)
		{
			if (data.hasOwnProperty(key))
				state[key] = data[key];
		}
	},
	setLoadingStatus (state, status) {
		state.loading = status
	},
	setPlanetResources (state, resources)
	{
		for (let res in resources)
		{
			if (resources.hasOwnProperty(res))
				state.resources[res]['current'] = resources[res]
		}
	}
}