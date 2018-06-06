<template>
	<div>
		<h3 class="mb-5">{{ meta.total }} comments</h3>

		<new-comment 
			:endpoint="endpoint"
		/>
		
		<template v-if="comments.length">
			<ul class="list-unstyled">
				<comment 
					v-for="comment in comments"
					:key="comment.id"
					:comment="comment"
				/>
			</ul>

			<a
				href="#" 
				class="btn btn-light btn-block"
				@click="loadMore"
				v-if="meta.current_page < meta.last_page"
			>Show more</a>
		</template>

		<p v-else class="mt-4">No comments to display</p>
	
	</div>
</template>

<script>
	import axios from 'axios';
	import Comment from './Comment';
	import NewComment from './NewComment';
	import bus from '../../bus';

	export default {
		data() {
			return {
				comments: [],
				meta: {}
			}
		},
		props: {
			endpoint: {
				required: true,
				type: String
			}
		},
		components: {
			Comment,
			NewComment
		},
		methods: {
			async loadComments(page = 1) {
				let comments = await axios.get(`${this.endpoint}?page=${page}`);

				this.comments = comments.data.data;
				this.meta = comments.data.meta; // For pagination
			},
			async fetchMeta() {
				let comments = await axios.get(`${this.endpoint}?page=${this.meta.current_page}`);

				this.meta = comments.data.meta;
			},
			async loadMore() {
				let comments = await axios.get(`${this.endpoint}?page=${this.meta.current_page + 1}`);

				this.comments.push(...comments.data.data);
				this.meta = comments.data.meta;

			},
			async prependComment(comment) {
				this.comments.unshift(comment);

				await this.fetchMeta();

				if(this.meta.current_page < this.meta.last_page) {
					this.comments.pop();
				}
			}
		},
		mounted() {
			this.loadComments();
			bus.$on('comment:stored', this.prependComment);
		}

	}
</script>