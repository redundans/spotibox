var app = new Vue({
    el: '#app',
    data: {
        nowplaying: null,
        playlist: null,
        message: 'Hello Vue!',
        showSearch: false,
        search: null,
        searchResult: false,
        cart: [],
        polling: null
    },
    created: function () {
        this.pollData();
    },
    filters: {
        readableArray: function ( b ) {
            let a = b.map(function (obj) {
              return obj.name;
            });
            return a.length == 1 ? a[0] : [ a.slice(0, a.length - 1).join(", "), a[a.length - 1] ].join(" & ");
        },
        millisToMinutesAndSeconds: function ( millis ) {
          let minutes = Math.floor(millis / 60000);
          let seconds = ((millis % 60000) / 1000).toFixed(0);
          return minutes + ":" + (seconds < 10 ? '0' : '') + seconds;
        }
    },
    methods:{
        loadNowPlaying: function (e) {
            const vm = this;
            fetch( '/wp-json/spotibox/v1/nowplaying' ).then(function (response) {
                // The API call was successful!
                return response.json();
            }).then(function (data) {
                vm.nowplaying = data;
            }).catch((error) => {
                vm.nowplaying = null;
            });
        },
        loadPlaylist: function (e) {
            const vm = this;
            fetch( '/wp-json/spotibox/v1/playlist' ).then(function (response) {
                // The API call was successful!
                return response.json();
            }).then(function (data) {
                vm.playlist = data;
            });
        },
        doSearch: function (e) {
            const vm = this;
            e.preventDefault();
            if ( ! this.search ) {
                return false;
            }
            let data = {
                search: this.search
            };
            fetch(
                '/wp-json/spotibox/v1/search',
                {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }
            ).then(function (response) {
                // The API call was successful!
                return response.json();
            }).then(function (data) {
                vm.searchResult = data;
            });
        },
        addToCart: function (item) {
            if ( ! this.cart.find( ({ id }) => id === item.id ) ) {
                this.cart.push(item);
            }
        },
        clearCart: function (e) {
            this.cart = [];
        },
        addCartToList: function () {
            const vm = this;
            let data = { cart: vm.cart };
            fetch(
                '/wp-json/spotibox/v1/addtoplaylist',
                {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }
            ).then(function (response) {
                // The API call was successful!
                return response.json();
            }).then(function (data) {
                vm.cart = [];
                vm.showSearch = false;
                vm.loadPlaylist();
            });
        },
        pollData () {
            this.polling = setInterval(() => {
                this.loadPlaylist();
                this.loadNowPlaying();
            }, 1000)
        }
    }
});