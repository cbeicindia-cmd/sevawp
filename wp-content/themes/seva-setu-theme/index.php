<?php get_header(); ?>
<section class="hero">
    <h2>Welcome to SEVA SETU KENDRA</h2>
    <p>Search and apply for government schemes through certified Seva Setu Agents.</p>
</section>

<section>
    <h3>Core Services</h3>
    <div class="card-grid">
        <article class="card"><h4>Government Schemes</h4><p>Browse schemes by state, income, and category.</p></article>
        <article class="card"><h4>Become Agent</h4><p>Register and support citizens as an authorized agent.</p></article>
        <article class="card"><h4>Citizen Portal</h4><p>Track your applications and receive recommendations.</p></article>
        <article class="card"><h4>AI Scheme Finder</h4><p>Get smart eligibility-based recommendations.</p></article>
    </div>
</section>

<section>
    <?php echo do_shortcode('[seva_setu_ai_finder]'); ?>
</section>
<?php get_footer(); ?>
