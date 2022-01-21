<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Spotibox
 */

get_header();
?>
<div id="app" >
	<div v-if="showSearch" class="bg-lime-500 min-h-screen text-lime-800">
		<div class="container mx-auto font-light text-sm lg:text-base px-6 py-6 lg:py-9">
			<button v-on:click="showSearch=!showSearch" class="px-3 py-1 rounded-full text-sm border border-lime-800">Close</button>

			<form class="my-6" @submit="doSearch">
				<input v-model="search" type="search" name="s" class="block w-full rounded-full bg-lime-300 px-3 py-2 placeholder:text-lime-800" placeholder="Search track">
			</form>

			<div v-if="searchResult">
				<ul class="border-t border-lime-800 my-9 py-5">
					<li v-for="item in searchResult.tracks.items" class="py-2 flex flex-row justify-items-start">
						<div class="grow flex flex-col lg:flex-row">
							<span class="font-medium mr-3">{{item.name}}</span>
							<span class="italic">{{item.artists | readableArray }}</span>
						</div>
						<button v-on:click="addToCart(item)">+</button>
					</li>
				</ul>
			</div>

		</div>
	</div>

	<div v-if="!showSearch" class="container mx-auto font-light text-sm lg:text-base">
		<div class="flex flex-col px-6">

			<!-- Start Now Playing -->
			<div id="nowplaying" v-if="nowplaying" class="flex flex-row gap-6 lg:gap-9 items-center py-9 border-b">
				<div id="cover" class="w-5/12 lg:w-3/12">
					<img v-bind:src="nowplaying.item.album.images[0].url" class="drop-shadow" />
				</div>
				<div id="info" class="w-6/12 lg:w-9/12 text-sm lg:text-base">
					<h1 class="font-bold text-base lg:text-2xl leading-none">{{nowplaying.item.name}}</h1>
					<p class="italic my-1">{{nowplaying.item.artists | readableArray}}</p>
					<p class="mt-1">
						<span class="font-medium mr-3">{{nowplaying.item.album.name}}</span>
						<span class="opacity-25">{{nowplaying.item.album.release_date}}</span>
					</p>
				</div>
			</div>
			<div v-if="!nowplaying" id="noplaying" class="my-9 flex flex-row gap-6 lg:gap-9 items-center">
				<div id="cover" class="w-5/12 lg:w-3/12">
					<div class="border text-white drop-shadow w-full aspect-square flex items-center justify-center bg-black">
						<span class="font-bold text-9xl">?</span>
					</div>
				</div>
				<div class="w-6/12 lg:w-9/12 text-sm lg:text-base">
					<h1 class="font-bold text-base lg:text-2xl leading-none">No track playing right now!</h1>
					<p class="italic my-1">No artist</p>
					<p class="mt-1">
						<span class="font-medium mr-3">No album</span>
					</p>
				</div>
			</div>
			<!-- End Now Playing -->

			<!-- Start Search Button -->
			<div class="py-6 sticky top-0 bg-white border-b flex flex-row justify-between items-center">
				<span class="font-bold">Comming tracks</span>
				<button class="border px-3 py-1 rounded-full text-sm hover:text-white hover:bg-black" v-on:click="showSearch=!showSearch">+ Add tracks</button>
			</div>
			<!-- End Search Button -->

			<!-- Start Playlist -->
			<ul id="playlist" v-if="playlist" class="my-6">
				<li v-for="track in playlist.tracks.items" class="py-1 flex flex-row justify-items-start">
					<div class="grow flex flex-col lg:flex-row">
						<span class="font-medium mr-3">{{track.track.name}}</span>
						<span class="italic">{{track.track.artists | readableArray }}</span>
					</div>
					<span class="text-gray-500">{{track.track.duration_ms | millisToMinutesAndSeconds}}</span>
				</li>
			</ul>
			<!-- Start Playlist -->

		</div>
	</div>

	<!-- Start Cart -->
	<div v-if="cart.length!=0" id="cart" class="fixed w-full bottom-0 bg-lime-500 drop-shadow">
		<div class="container mx-auto font-light text-sm lg:text-base">
			<div class="px-6 py-6 flex flex-row items-center justify-between">
				<div class="font-light text-lime-800">
					<strong>{{cart.length}}</strong> tracks
					<a class="text-sm mx-1 cursor-pointer hover:opacity-25" v-on:click="clearCart">(remove)</a>
				</div>
				<button v-on:click="addCartToList" class="font-bold block rounded-full text-lime-800 border border-lime-800 px-3 py-2 hover:bg-lime-900 hover:text-lime-500">Add to list</button>
			</div>
		</div>
	</div>
	<!-- End Cart -->

</div>
<?php
get_footer();
