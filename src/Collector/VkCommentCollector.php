<?php

namespace App\Collector;

use App\Exception\CollectException;
use App\Parser\ParserFactory;
use App\Request\VkPublicRequest;
use Psr\Log\LoggerInterface;
use Schema\Parse\Record\Source;

class VkCommentCollector implements CollectorInterface
{
    private $request;
    private $logger;
    private $parser_factory;

    private $done      = false;
    private $offset    = 0;
    private $uniqueIds = [];

    public function __construct(
        VkPublicRequest $request,
        ParserFactory $parser_factory,
        LoggerInterface $logger
    )
    {
        $this->request        = $request;
        $this->logger         = $logger;
        $this->parser_factory = $parser_factory;
    }

    public function collect(Source $source, string $period): Result
    {
        $sourceParams = json_decode($source->getParameters(), true);

        foreach (['group_id', 'topic_id'] as $key) {
            if (!isset($sourceParams[$key])) {
                $this->logger->error('Source params has not key', [
                    'key'         => $key,
                    'source_id'   => $source->getId(),
                    'source_type' => $source->getType(),
                    'parameters'  => $source->getParameters()
                ]);

                throw new CollectException(sprintf('Source params has not key \'%s\'', $key));
            }
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

        $commentRecordsResponse = $this->request->getCommentRecords(
            $sourceParams['group_id'],
            $sourceParams['topic_id'],
            100,
            $this->offset
        );

        $commentRecords = json_decode((string)$commentRecordsResponse->getBody(), true);
        if (!isset($commentRecords['response']['items'])) {

            $this->logger->error('Response has invalid json', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
                'response'    => (string)$commentRecordsResponse->getBody()
            ]);

            throw new CollectException('Response has invalid json');
        }

        if (empty($commentRecords['response']['items'])) {
            $this->done   = true;
            $this->offset = 0;

            $this->logger->debug('Empty response', [
                'source_id'   => $source->getId(),
                'source_type' => $source->getType(),
            ]);

            return new Result($this->done);
        }

        $items          = [];
        $validTimestamp = (new \DateTime())->modify(sprintf('- %s', $period))->getTimestamp();
        foreach ($commentRecords['response']['items'] as $commentRecord) {
            $parser = $this->parser_factory->init($source, $commentRecord);
            $id     = $parser->id();

            $this->logger->debug('Handling item...', ['id' => $id]);

            $uniqueId = $source->getId() . '-' . $id;
            if (in_array($uniqueId, $this->uniqueIds)) {
                $this->logger->debug('Item already handled', ['id' => $id]);

                continue;
            }

            $this->uniqueIds[] = $uniqueId;

            $timestamp = $parser->timestamp();

            if ($validTimestamp > $timestamp) {
                $this->logger->debug('Items $validTimestamp > $timestamp', ['id' => $id]);
                $this->done = true;

                break;
            }

            $this->logger->debug('Handling item done', ['id' => $id]);

            $items[] =
                (new RawData())
                    ->setId($source->getId() . '-' . $id)
                    ->setLink($parser->link($id))
                    ->setTimestamp($timestamp)
                    ->setContent($commentRecord);
        }

        $this->offset = $this->done ? 0 : $this->offset + 100;

        return new Result($this->done, $items);
    }
}

