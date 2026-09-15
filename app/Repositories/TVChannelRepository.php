<?php

namespace App\Repositories;

use App\Models\TvChannel;
use App\Tenancy\TenantContext;
use Yajra\DataTables\Facades\DataTables;

class TVChannelRepository extends BaseRepository
{
    public function __construct(TvChannel $channel)
    {
        parent::__construct($channel);
    }

    public function findUid($uid)
    {
        $query = $this->accessibleQuery();

        return $query->where('tv_channels.uuid', $uid)->first();
    }

    public function updateByUid($uid, array $attributes)
    {
        $record = $this->findUid($uid);

        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $attributes['updated_by'] = auth()->user()->id ?? null;

        if ($record) {
            $record->update($attributes);

            return $record;
        }

        return false;
    }

    public function delete($uid, $fieldName = 'logo', $destroyImage = true)
    {
        $record = $this->findUid($uid);
        if ($record) {
            $record->deleted_by = auth()->user()->id ?? null;
            $record->deleted_at = now();

            return $record->save();
        }

        return false;
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'logo', $destroyImage = true)
    {
        if (empty($uids)) {
            return 0;
        }

        return $this->model->whereIn('uuid', $uids)->update([
            'deleted_by' => auth()->user()->id ?? null,
            'deleted_at' => now(),
        ]);
    }

    public function getDatatable()
    {
        $query = $this->accessibleQuery();
        $search = request('search.value');
        $filters = request('filters', []);
        $isMasterCatalog = $this->isMasterCatalog();
        $query->when($search, fn ($q) => $q->where(fn ($sub) => $sub
            ->where('tv_channels.name', 'like', '%'.$search.'%')
            ->when(! $isMasterCatalog, fn ($hotelQuery) => $hotelQuery->orWhere('hotel_tv_channel.custom_name', 'like', '%'.$search.'%'))
            ->orWhere('tv_channels.slug', 'like', '%'.$search.'%')));
        $query->when($filters['type'] ?? null, fn ($q, $type) => $isMasterCatalog
            ? $q->where('tv_channels.type', $type)
            : $q->whereRaw('COALESCE(hotel_tv_channel.custom_type, tv_channels.type) = ?', [$type]));
        $query->when($filters['region'] ?? null, fn ($q, $region) => $isMasterCatalog
            ? $q->where('tv_channels.region', $region)
            : $q->whereRaw('COALESCE(hotel_tv_channel.custom_region, tv_channels.region) = ?', [$region]));
        if (($filters['is_active'] ?? '') !== '') {
            $query->where($this->isMasterCatalog() ? 'tv_channels.is_active' : 'hotel_tv_channel.is_active', (bool) $filters['is_active']);
        }

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('logo', function ($row) {
                $media = $this->isMasterCatalog() ? $row->imageMedia : ($row->hotelImageMedia ?: $row->imageMedia);
                $logo = $media ? getMediaImageUrl($media->storage_path, 80, 80) : $row->source_logo_url;

                return $logo
                    ? '<img src="'.e($logo).'" alt="" style="width:38px;height:30px;object-fit:contain" loading="lazy">'
                    : '<i class="fa fa-tv text-muted"></i>';
            })
            ->addColumn('action', function ($row) {
                return $this->isMasterCatalog()
                    ? view('partials.datatable.action2', ['row' => $row])->render()
                    : view('pages.tv_channels.assignment-action', ['row' => $row])->render();
            })
            ->rawColumns(['logo', 'action'])
            ->make(true);
    }

    private function accessibleQuery()
    {
        $hotel = app(TenantContext::class)->hotel();

        if ($hotel?->is_system) {
            return TvChannel::query()->where('tv_channels.hotel_id', $hotel->id);
        }

        return TvChannel::query()->withoutGlobalScope('hotel')
            ->join('hotel_tv_channel', 'hotel_tv_channel.tv_channel_id', '=', 'tv_channels.id')
            ->where('hotel_tv_channel.hotel_id', $hotel?->id)
            ->whereNull('tv_channels.deleted_at')
            // Do not select the master stream URL for a hotel-scoped CMS user.
            // Player delivery uses its own authenticated API query, so hiding it
            // here does not interfere with playback.
            ->select(
                'tv_channels.id',
                'tv_channels.uuid',
                'tv_channels.hotel_id',
                'tv_channels.slug',
                'tv_channels.tvg_id',
                'tv_channels.group_title',
                'tv_channels.source_type',
                'tv_channels.source_logo_url',
                'tv_channels.source_hash',
                'tv_channels.image_id',
                'tv_channels.created_by',
                'tv_channels.updated_by',
                'tv_channels.deleted_by',
                'tv_channels.created_at',
                'tv_channels.updated_at',
                'tv_channels.deleted_at',
                'tv_channels.name as master_name',
                'tv_channels.type as master_type',
                'tv_channels.region as master_region',
                'tv_channels.frequency as master_frequency',
                'tv_channels.quality as master_quality',
                'hotel_tv_channel.is_active as assignment_is_active',
                'hotel_tv_channel.sort_order as assignment_sort_order',
                'hotel_tv_channel.custom_name',
                'hotel_tv_channel.custom_type',
                'hotel_tv_channel.custom_region',
                'hotel_tv_channel.custom_stream_url',
                'hotel_tv_channel.custom_frequency',
                'hotel_tv_channel.custom_quality',
                'hotel_tv_channel.custom_image_id')
            ->selectRaw('COALESCE(hotel_tv_channel.custom_name, tv_channels.name) as name')
            ->selectRaw('COALESCE(hotel_tv_channel.custom_type, tv_channels.type) as type')
            ->selectRaw('COALESCE(hotel_tv_channel.custom_region, tv_channels.region) as region')
            ->selectRaw('hotel_tv_channel.custom_stream_url as stream_url')
            ->selectRaw('COALESCE(hotel_tv_channel.custom_frequency, tv_channels.frequency) as frequency')
            ->selectRaw('COALESCE(hotel_tv_channel.custom_quality, tv_channels.quality) as quality')
            ->selectRaw('hotel_tv_channel.sort_order as sort_order')
            ->selectRaw('hotel_tv_channel.is_active as is_active')
            ->orderBy('hotel_tv_channel.sort_order')
            ->orderBy('tv_channels.name');
    }

    private function isMasterCatalog(): bool
    {
        return (bool) app(TenantContext::class)->hotel()?->is_system;
    }
}
