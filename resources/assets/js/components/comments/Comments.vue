<template>
	<div>
		<template v-if="reply">
			<comment-reply :comment="reply" />
		</template>

		<template v-else>
			<h3 class="mb-5">{{ meta.total }} {{ pluralize('comment', meta.total) }}</h3>

			<new-comment 
				:endpoint="endpoint"
				v-if="user.authenticated"
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
		</template>
	</div>
</template>

<script>
	import axios from 'axios';
	import Comment from './Comment';
	import NewComment from './NewComment';
	import CommentReply from './CommentReply';
	import VueScrollTo from 'vue-scrollto';
	import bus from '../../bus';

	export default {
		data() {
			return {
				comments: [],
				meta: {},
				reply: null
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
			NewComment,
			CommentReply
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
			async loadOneAfterDeletion() {
				if(this.meta.current_page >= this.meta.last_page) {
					return;
				}

				let comments = await axios.get(`${this.endpoint}?page=${this.meta.current_page}`);

				this.comments.push(comments.data.data[comments.data.data.length - 1]);
				this.meta = comments.data.meta;

			},
			async prependComment(comment) {
				this.comments.unshift(comment);

				await this.fetchMeta();

				if(this.meta.current_page < this.meta.last_page) {
					this.comments.pop();
				}
			},
			setReplying(comment) {
				this.reply = comment;
			},
			appendReply(comment, reply) {
				_.find(this.comments, { id: comment.id}).replies.push(reply);
			},
			editComment(comment) {
				if(comment.isReply) {
					let parentComment = _.find(this.comments, { id : comment.parent_id });
					
					let reply = parentComment.replies.find((child) => {
						return child.id === comment.id;
					});

					_.assign(reply, comment);

					return;
				}

				_.assign(_.find(this.comments, { id: comment.id }), comment);
			},
			deleteComment(comment) {
				if(comment.isReply) {
					let parentComment = _.find(this.comments, { id: comment.parent_id });

					parentComment.replies = parentComment.replies.filter((reply) => {
						return reply.id !== comment.id;
					});

					return;
				}

				this.comments = this.comments.filter((com) => com.id !== comment.id);
				this.meta.total--;

				this.loadOneAfterDeletion();
			}
		},
		mounted() {
			this.loadComments();
			bus.$on('comment:stored', this.prependComment);
			bus.$on('comment:reply', this.setReplying);
			bus.$on('comment:reply-cancelled', () => this.reply = null);
			bus.$on('comment:replied', ({comment, reply}) => {
				this.appendReply(comment, reply);

				setTimeout(() => {
					VueScrollTo.scrollTo(`#comment-${reply.id}`, 500);
				}, 100);
				
			});
			bus.$on('comment:edited', this.editComment);
			bus.$on('comment:deleted', this.deleteComment);
		}

	}
</script>