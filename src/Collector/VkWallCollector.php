<?php

namespace App\Collector;

use App\Exception\CollectException;
use App\Parser\ParserFactory;
use App\Request\VkPublicRequest;
use Psr\Log\LoggerInterface;
use Schema\Parse\Record\Source;

class VkWallCollector implements CollectorInterface
{
    private $request;
    private $logger;
    private $parser_factory;

    private $done       = false;
    private $offset     = 0;
    private $unique_ids = [];

    public function __construct(
        VkPublicRequest $request,
        ParserFactory $parser_factory,
        LoggerInterface $logger
    )
    {
        $this->request    = $request;
        $this->logger     = $logger;
        $this->unique_ids = [];

        $this->parser_factory = $parser_factory;
    }

    public function collect(Source $source, string $period): Result
    {
        $sourceParams = json_decode($source->getParameters(), true);
        if (!isset($sourceParams['owner_id'])) {
            $this->logger->error('Source params has not key \'owner_id\'', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'parameters'  => $source->getParameters()
            ]);

            throw new CollectException('Source params has not key \'owner_id\'');
        }

        if ($this->done) {
            $this->offset = 0;

            $this->logger->debug('There are no more notes', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType()
            ]);

            return new Result($this->done);
        }

        usleep(500000);

        $wallRecordsResponse = $this->request->getWallRecords($sourceParams['owner_id'], 100, $this->offset);

        $wallRecords = json_decode((string)$wallRecordsResponse->getBody(), true);
        if (!isset($wallRecords['response']['items'])) {

            $this->logger->error('Response has invalid json', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'response'    => (string)$wallRecordsResponse->getBody()
            ]);

            throw new CollectException('Response has invalid json');
        }

        if (empty($wallRecords['response']['items'])) {
            $this->done   = true;
            $this->offset = 0;

            $this->logger->debug('Empty response', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
            ]);

            return new Result($this->done);
        }

        $items          = [];
        $validTimestamp = (new \DateTime())->modify('- ' . $period)->getTimestamp();

        foreach ($wallRecords['response']['items'] as $wallRecord) {

            $id = $wallRecord['id'];

            $this->logger->debug('Handling item...', ['id' => $id]);

            $unique_id = $source->getId() . '-' . $wallRecord['id'];
            if (in_array($unique_id, $this->unique_ids)) {
                $this->logger->debug('Item already handled', ['id' => $id]);
                continue;
            }

            $this->unique_ids[] = $unique_id;

            if (isset($wallRecord['marked_as_ads'])) {
                $this->logger->debug('Item marked_as_ads', ['id' => $id]);
                continue;
            }

            if (isset($wallRecord['is_pinned'])) {
                $this->logger->debug('Item is_pinned', ['id' => $id]);
                continue;
            }

            if ($validTimestamp > $wallRecord['date']) {
                $this->done = true;
                $this->logger->debug('Item $validTimestamp > $wallRecord[\'date\']', ['id' => $id]);

                break;
            }

            $this->logger->debug('Handling item done', ['id' => $id]);

            $parser = $this->parser_factory->init($source, $wallRecord);

            $id      = $parser->id();
            $items[] =
                (new RawData())
                    ->setId($source->getId() . '-' . $id)
                    ->setLink($parser->link($id))
                    ->setTimestamp($parser->timestamp())
                    ->setContent($wallRecord);
        }

        $this->offset = $this->done ? 0 : $this->offset + 100;

        return new Result($this->done, $items);
    }
}

