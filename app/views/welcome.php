<?php
$pageTitle = "Welcome";
require_once 'app/views/layout/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title">What is this project?</h3>
                <p class="card-text">
                    This research project aims to compare human-to-human and AI-to-human conversations in a pastoral care context. 
                    Participants will engage in a chat conversation and complete surveys before and after the interaction.
                </p>
                <p class="card-text">
                    The purpose of this study is to better understand the differences between human chaplain care and AI-based spiritual support, 
                    and how these different types of interactions might be perceived by those seeking spiritual guidance.
                </p>
                <p class="card-text">
                    Your participation in this study is completely voluntary, and all of your responses will remain confidential.
                </p>
                <div class="text-center mt-4">
                    <a href="index.php?page=consent" class="btn btn-primary btn-lg">Join the Research Study</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'app/views/layout/footer.php';
?> 