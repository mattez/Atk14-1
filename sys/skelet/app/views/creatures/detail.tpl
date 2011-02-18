<h2>{$page_title}</h2>

<table>
	<tbody>
		<tr>
			<th>{t}Name{/t}</th>
			<td>{$creature->getName()|h}</td>
		</tr>
		<tr>
			<th>{t}Description{/t}</th>
			<td>{$creature->getDescription()|h|nl2br}</td>
		</tr>
		<tr>
			<th>{t}Image{/t}</th>
			<td>
				{if $creature->hasImage()}
					<img src="{$creature->getImageUrl()|h}" alt="{t}An image of the creature{/t}" />
				{else}
					&mdash;
				{/if}
			</td>
		</tr>
	</tbody>
</table>

<ul>
<li>{a action=detail id=$creature format=json}Get the creature in JSON{/a}</li>
<li>{a action=detail id=$creature format=xml}Get the creature in XML{/a}</li>
</ul>
