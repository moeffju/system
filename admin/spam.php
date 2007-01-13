<div id="content-area">
	<div class="dashbox c3" id="welcome">
		<h1>Unapproved Comments</h1>
		<?php if( Comments::count_total( Comment::STATUS_SPAM ) ) { ?>
		<p>Below you will find comments awaiting moderation.</p>
	<form method="post" name="moderation">
	<p class="submit"><input type="submit" name="moderate" value="Moderate!" /> <input type="checkbox" name="mass_delete" id="mass_delete" value="mass_delete">Delete 'em all</p>
	<ul id="waiting">
		<?php foreach( Comments::get( array( 'status' => Comment::STATUS_SPAM, 'limit' => 30, 'orderby' => 'date DESC' ) ) as $comment ){ ?>
		<li>
			Comment by <?php echo $comment->name;?> on <a href="<?php URL::get( 'post', array( 'slug' => $comment->post_slug ) ); ?>"><?php echo $comment->post_slug; ?></a>
			<br /><small>(Commented created on <?php echo $comment->date; ?>)</small>
			<p><?php echo $comment->content; ?></p>
			<span class="manage">
				<p>Action: 
				<label>
					<input type="checkbox" name="approve[<?php echo $comment->id; ?>]" id="approve-<?php echo $comment->id; ?>" value="<?php echo $comment->id; ?>">Approve
				</label>
				<label>
				<input type="checkbox" name="delete[<?php echo $comment->id; ?>]" id="delete-<?php echo $comment->id; ?>" value="<?php echo $comment->id; ?>">Delete
				</label>
				<label>
				<input type="checkbox" name="Ignore" id="spam-<?php echo $comment->id; ?>" value="" checked="checked">Ignore
				</label>
				</p>
			</span><br />
		</li>
		<?php }	?>
	</ul>
	<p class="submit"><input type="submit" name="moderate" value="Moderate!" /> <input type="checkbox" name="mass_delete" id="mass_delete" value="mass_delete">Delete 'em all</p>
	</form>
	<?php } else { ?>
		<p>You currently have no comments to moderate</p>
	<?php } ?>
	</div>
</div>