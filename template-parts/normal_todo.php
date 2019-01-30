<?php
	$content = unserialize($task_tmp->content);
?>

<li class="normal" data-type="0" data-id="<?php echo $task_tmp->task_id; ?>">
	<h3>Normal ToDo</h3>
	<span class="toggle-indicator" aria-hidden="true"></span>

	<div class="content" style="display:none;">
		<div class="top_form">
			<input type="text" name="task_name" placeholder="Task name" value="<?php echo $task_tmp->name; ?>"></input>
			<input type="number" name="task_points" placeholder="Task points" value="<?php echo $task_tmp->points; ?>"></input>
		</div>
		<div class="bottom_form">
			<div class="intro">
				<h5>Introduction</h5>
				<label>
					Add an introduction for the ToDo, you can use a single video or a description with an image/gif.
				</label>
				<div class="intro_content">
					<div class="image">
						<button class="button button-large change-media">Change Image</button>
						<img class="intro_image" src="<?php echo $content['intro']['image']; ?>" alt="Introduction image">
					</div>
					<input value="<?php echo $content['intro']['title']; ?>" type="text" class="intro_title" placeholder="Intro Title">
					<input value="<?php echo $content['intro']['video']; ?>" type="text" class="intro_video" placeholder="Wistia ID - Optional -">
					<textarea name="intro_text" placeholder="Introduction text">
						<?php echo $content['intro']['desc']; ?>
					</textarea>
				</div>
			</div>
			<div class="content">
				<h5>ToDo Content</h5>
				<input value="<?php echo $content['content']['title']; ?>" type="text" class="task_title" placeholder="Content Title">
				<input value="<?php echo $content['content']['video']; ?>" type="text" class="task_video" name="task_video" placeholder="Wistia video ID"></input>
				<textarea name="task_description" placeholder="Task Description">
					<?php echo $content['content']['desc']; ?>
				</textarea>
			</div>
			<div class="outro">
				<h5>Outro</h5>
				<label>Add an outro for the ToDo.</label>
				<div class="outro_content">
					<div class="image">
						<button class="button button-large change-media">Change Image</button>
						<img class="outro_image" src="<?php echo $content['outro']['image']; ?>" alt="Outro image">
					</div>
					<input value="<?php echo $content['outro']['title']; ?>" type="text" class="outro_title" placeholder="Outro Title">
					<input value="<?php echo $content['outro']['video']; ?>" type="text" class="outro_video" placeholder="Wistia ID - Optional -">
					<textarea name="outro_text" placeholder="Introduction text">
						<?php echo $content['outro']['desc']; ?>
					</textarea>
				</div>
			</div>
		</div>
		<div class="actions">
			<button type="submit" class="button button-large update-task">Update</button>
			<button type="submit" class="button button-large remove-task">Remove</button>
		</div>
	</div>
</li>