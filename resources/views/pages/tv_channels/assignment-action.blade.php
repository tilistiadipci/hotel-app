<button type="button" class="btn btn-sm btn-secondary" title="Lihat detail channel" onclick="showModalDetail('{{ route('tv-channels.show', $row->uuid) }}')"><i class="fa fa-cog"></i></button>
<a href="{{ route('tv-channels.assignment.edit', $row->uuid) }}" class="btn btn-sm btn-primary" title="Kelola channel untuk hotel"><i class="fa fa-edit"></i></a>
