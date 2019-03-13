export const state = () => ({
	isSocial: false,
	path: null,
	version: null,
	host: null,
	redirect: null,
	messages: null,
	page: null,
	stats: null,
	view: null,
	title: null,
	url: null,
	user: null,
	menu: null,
	chat: null,
	resources: null,
	start_time: Math.floor(((new Date()).getTime()) / 1000),
	loading: false,
});