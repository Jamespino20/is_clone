/* Lightweight fuzzy search scaffold using simple scoring.
   If Fuse.js is later added, this module can delegate to it. */
(function () {
	"use strict";

	function normalize(s) {
		return (s || "").toString().toLowerCase();
	}

	function scoreMatch(query, text) {
		// Simple subsequence score: contiguous bonus, start-of-word bonus
		query = normalize(query);
		text = normalize(text);
		if (!query) return 0;
		let qi = 0, score = 0, streak = 0;
		for (let i = 0; i < text.length && qi < query.length; i++) {
			if (text[i] === query[qi]) {
				qi++; streak++; score += 2;
				if (i === 0 || text[i - 1] === ' ') score += 1; // word start bonus
			} else {
				streak = 0;
			}
			if (streak > 1) score += 1; // contiguous bonus
		}
		return qi === query.length ? score : 0;
	}

	function flattenIndex(index) {
		// index: { sectionName: [items...] }
		const out = [];
		Object.keys(index || {}).forEach((k) => {
			(index[k] || []).forEach((item) => {
				out.push({ section: k, item });
			});
		});
		return out;
	}

	async function fetchIndex() {
		try {
			const res = await fetch('api/sample_data.php?action=index', { credentials: 'same-origin' });
			const data = await res.json();
			return data?.index || {};
		} catch (e) {
			console.warn('Search index fetch failed', e);
			return {};
		}
	}

    async function search(query, filter = 'all') {
		const idx = await fetchIndex();
		const pool = flattenIndex(idx).filter((row) => filter === 'all' || row.section === filter);
		const scored = pool.map((row) => {
			const label = row.item?.label || row.item?.title || row.item?.name || '';
			const s = scoreMatch(query, label);
			return { ...row, score: s };
		}).filter(r => r.score > 0)
		  .sort((a, b) => b.score - a.score)
		  .slice(0, 15);
		return scored;
	}

    function renderResults(results) {
		const list = document.getElementById('globalSearchResults');
		if (!list) return;
		list.innerHTML = '';
		results.forEach((r) => {
			const li = document.createElement('div');
			li.className = 'search-result-item';
			const label = r.item?.label || r.item?.title || r.item?.name || 'Result';
			li.innerHTML = `<span class="sr-section">${r.section}</span><span class="sr-label">${label}</span>`;
			li.addEventListener('click', () => {
				if (r.item?.href) window.location.href = r.item.href;
			});
			list.appendChild(li);
		});
		list.style.display = results.length ? 'block' : 'none';
	}

	async function wireGlobalSearch() {
		const input = document.getElementById('globalSearch');
		if (!input) return;
		const filterSel = document.getElementById('searchFilter');
		const resultsBox = document.createElement('div');
		resultsBox.id = 'globalSearchResults';
		resultsBox.className = 'search-results-box';
		input.parentElement?.appendChild(resultsBox);

        let lastQuery = '';
        input.addEventListener('input', async () => {
			const q = input.value.trim();
			if (q === lastQuery) return;
			lastQuery = q;
            if (!q) {
                renderResults([]);
                // Restore all dashboard items visibility
                try {
                    const cards = Array.from(document.querySelectorAll('.action-card'));
                    const stats = Array.from(document.querySelectorAll('.stat-item'));
                    cards.forEach(c => c.style.display = '');
                    stats.forEach(s => s.style.display = '');
                    const hint = document.getElementById('dashboardEmptySearch');
                    hint && hint.remove();
                } catch (e) { /* ignore */ }
                return;
            }
			const filter = filterSel?.value || 'all';
			const items = await search(q, filter);
			renderResults(items);

            // Live filter dashboard items (cards and actions)
            try {
              const cards = Array.from(document.querySelectorAll('.action-card'));
              const stats = Array.from(document.querySelectorAll('.stat-item'));
              const qn = q.toLowerCase();
              const matchText = (el) => (el.textContent || '').toLowerCase().includes(qn);
              let anyShown = false;
              if (cards.length) {
                cards.forEach(c => {
                  const show = matchText(c);
                  c.style.display = show ? '' : 'none';
                  if (show) anyShown = true;
                });
              }
              if (stats.length) {
                stats.forEach(s => {
                  const show = matchText(s);
                  s.style.display = show ? '' : 'none';
                  if (show) anyShown = true;
                });
              }
              const emptyHintId = 'dashboardEmptySearch';
              let hint = document.getElementById(emptyHintId);
              if (!anyShown && (cards.length || stats.length)) {
                if (!hint) {
                  hint = document.createElement('div');
                  hint.id = emptyHintId;
                  hint.className = 'text-center text-muted py-3';
                  hint.textContent = 'No matching items. Try other keywords.';
                  const container = document.querySelector('main.container');
                  container && container.insertBefore(hint, container.firstChild);
                }
              } else if (hint) {
                hint.remove();
              }
            } catch (e) { /* ignore */ }
		});
	}

	document.addEventListener('DOMContentLoaded', wireGlobalSearch);
})();


