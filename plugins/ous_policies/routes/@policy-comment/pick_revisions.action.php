<?php

use DigraphCMS_Plugins\unmous\ous_policies\Revisions\RevisionState;

?>
<h1>Pick revisions</h1>
<p>
    Revisions selected here will have their status immediately set to
    <em><?php echo (new RevisionState('pending'))->label(); ?></em>.
    Once the comment period begins, revisions will have their state set to
    <em><?php echo (new RevisionState('comment'))->label(); ?></em>.
    Once the revision period ends they will once again be set to
    <em><?php echo (new RevisionState('pending'))->label(); ?></em>,
    and they will need to be manually published or cancelled.
</p>