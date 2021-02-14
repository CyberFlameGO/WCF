<article class="message messageReduced">
	<section class="messageContent">
		<header class="messageHeader">
			<div class="box32 messageHeaderWrapper">
				{user object=$message->getUserProfile() type='avatar32' ariaHidden='true'}
				
				<div class="messageHeaderBox">
					<h2 class="messageTitle">
						<a href="{@$message->getLink()}">{$message->getTitle()}</a>
					</h2>
					
					<ul class="messageHeaderMetaData">
						<li>{user object=$message->getUserProfile() class='username'}</li>
						<li><span class="messagePublicationTime">{@$message->getTime()|time}</span></li>
						
						{event name='messageHeaderMetaData'}
					</ul>
				</div>
			</div>
			
			{event name='messageHeader'}
		</header>
		
		<div class="messageBody">
			{event name='beforeMessageText'}
			
			<div class="messageText">
				{@$message->getFormattedMessage()}
			</div>
			
			{event name='afterMessageText'}
		</div>
	</section>
</article>
