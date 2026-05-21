<?php get_header(); ?>
<main class="layout"><section class="glass card"><h1>page-tajweed</h1>
<?php if('page-tajweed.php'==='search.php'): ?>
  <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>"><input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>"><button class="btn btn-premium">Search</button></form>
  <?php if(have_posts()): while(have_posts()): the_post(); ?><article><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></article><?php endwhile; else: ?><p>No results.</p><?php endif; ?>
<?php elseif('page-tajweed.php'==='404.php'): ?>
  <p>Page not found. Continue Quran journey from reader.</p><a class="btn btn-premium" href="<?php echo esc_url(home_url('/quran-reader')); ?>">Go Reader</a>
<?php else: ?>
  <div class="grid cards"><article class="card glass span-6">Dynamic Quran content widget</article><article class="card glass span-6">Progress and analytics panel</article></div>
<?php endif; ?>
</section></main>
<?php get_footer(); ?>
